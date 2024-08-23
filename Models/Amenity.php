<?php

namespace Models;

class Amenity
{
    private $wpdb;
    private $table = 'term_relationships';

    /** @var array */
    public array $amenities;
    public string $group_name;
    /** @var int */
    public int $post_id;

    public function __construct($wpdb)
    {
        $this->wpdb = $wpdb;
    }

    public function getAmenities(): array
    {
        $amenities = [];

        foreach ($this->amenities as $group) {
            foreach ($group['amenities'] as $amenity) {

                if ($amenity == '24-hour reception')
                    $amenity = '24-hour front desk';
                else if ($amenity == 'Free Wi-Fi')
                    $amenity = 'Free WiFi';

                $query_terms = $this->wpdb->prepare(
                    "SELECT term_id FROM " . $this->wpdb->prefix . "terms 
                    WHERE name = %s OR slug = %s",
                    $amenity,
                    str_replace(' ', '-', $amenity)
                );
                $amenity_found_terms = $this->wpdb->get_results($query_terms);

                if ($amenity_found_terms) {
                    foreach ($amenity_found_terms as $term) {
                        $query_term_taxonomy = $this->wpdb->prepare(
                            "SELECT term_taxonomy_id FROM " . $this->wpdb->prefix . "term_taxonomy 
                            WHERE term_id = %d AND taxonomy = 'hotel-facilities'",
                            $term->term_id
                        );
                        $amenity_found_term_taxonomy = $this->wpdb->get_results($query_term_taxonomy);

                        if ($amenity_found_term_taxonomy) {
                            echo '<br> Tax hotel found: <br>';
                            foreach ($amenity_found_term_taxonomy as $taxonomy) {
                                print_r($taxonomy);
                                if (!in_array($taxonomy->term_taxonomy_id, $amenities)) {
                                    $this->insertAmenity($taxonomy->term_taxonomy_id);
                                    $amenities[] = $taxonomy->term_taxonomy_id;
                                }
                            }
                        } else {
                            echo '<br> Tax hotel not found: <br>';
                            echo $amenity;
                            $this->createAmenity($amenity, 'hotel-facilities');
                        }
                    }
                } else {
                    echo '<br> Tax hotel not found: <br>';
                    echo $amenity;
                    $this->createAmenity($amenity, 'hotel-facilities');
                }
            }
        }

        return $amenities;
    }


    public function getRoomAmenities(): array
    {
        $amenities = [];

        foreach ($this->amenities as $amenity) {

            if ($amenity == '24-hour reception')
                $amenity = '24-hour front desk';
            else if ($amenity == 'Free Wi-Fi')
                $amenity = 'Free WiFi';

            $query_terms = $this->wpdb->prepare(
                "SELECT term_id FROM " . $this->wpdb->prefix . "terms 
                WHERE name = %s OR slug = %s",
                $amenity,
                str_replace(' ', '-', $amenity)
            );
            $amenity_found_terms = $this->wpdb->get_results($query_terms);

            if ($amenity_found_terms) {
                foreach ($amenity_found_terms as $term) {
                    $query_term_taxonomy = $this->wpdb->prepare(
                        "SELECT term_taxonomy_id FROM " . $this->wpdb->prefix . "term_taxonomy 
                        WHERE term_id = %d AND taxonomy = 'room-facilities'",
                        $term->term_id
                    );
                    $amenity_found_term_taxonomy = $this->wpdb->get_results($query_term_taxonomy);

                    if ($amenity_found_term_taxonomy) {
                        echo '<br> Tax room found: <br>';
                        foreach ($amenity_found_term_taxonomy as $taxonomy) {
                            print_r($taxonomy);
                            $this->insertAmenity($taxonomy->term_taxonomy_id);
                            $amenities[] = $taxonomy->term_taxonomy_id;
                        }
                    } else {
                        echo '<br> Tax room not found: <br>';
                        echo $amenity;
                        $this->createAmenity($amenity, 'room-facilities');
                    }
                }
            } else {
                echo '<br> Tax room not found: <br>';
                echo $amenity;
                $this->createAmenity($amenity, 'room-facilities');
            }
        }

        return $amenities;
    }


    public function insertAmenity($hotel_facility_number)
    {
        try {
            $query = $this->wpdb->prepare(
                "INSERT INTO {$this->wpdb->prefix}{$this->table} (object_id, term_taxonomy_id, term_order)
                VALUES (%d, %d, %d)
                ON DUPLICATE KEY UPDATE term_order = VALUES(term_order)",
                $this->post_id, $hotel_facility_number, 0
            );

            $this->wpdb->query($query);

            if ($this->wpdb->last_error) {
                throw new \Exception($this->wpdb->last_error);
            }

            return '<br>hotel facility inserted or updated successfully';

        } catch (\Exception $ex) {
            echo 'Caught exception: ', $ex->getMessage(), "\n";
        }
    }

    public function createAmenity ($amenity, $type_of_amenity){

        $select_query = $this->wpdb->prepare(
            "SELECT term_id FROM {$this->wpdb->prefix}terms
            WHERE name = %s OR slug = %s",
            $amenity,
            str_replace(' ', '-', $amenity)
        );

        $select_query_result = $this->wpdb->get_results($select_query);

        if ($select_query_result) {
            return;
        }

        $query = $this->wpdb->prepare(
            "INSERT INTO {$this->wpdb->prefix}terms (name, slug, term_group)
            VALUES (%s, %s, %d)
            ON DUPLICATE KEY UPDATE slug = VALUES(slug), name = VALUES(name), term_group = VALUES(term_group)",
            str_replace('-', ' ', $amenity), str_replace(' ', '-', $amenity), 0
        );

        $query_result = $this->wpdb->query($query);

        if ($this->wpdb->last_error) {
            throw new \Exception($this->wpdb->last_error);
        }

        $new_term_amenity = $this->wpdb->insert_id;

        $query = $this->wpdb->prepare(
            "INSERT INTO {$this->wpdb->prefix}term_taxonomy (term_id, taxonomy, description, parent, count)
            VALUES (%d, %s, %s, %d, %d)",
            $new_term_amenity, $type_of_amenity, '', 0, 0
        );

        $query_result = $this->wpdb->query($query);

        if ($this->wpdb->last_error) {
            throw new \Exception($this->wpdb->last_error);
        }

        $this->insertAmenity($this->wpdb->insert_id);

    }

}


?>
