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

        // foreach ($this->amenities as $group) {
            foreach ($this->amenities['amenities'] as $amenity) {

                echo $amenity;

                if ($amenity == '24-hour reception')
                    $amenity = '24-hour front desk';
                else if ($amenity == 'Free Wi-Fi')
                    $amenity = 'Free WiFi';

                $query_terms = $this->wpdb->prepare(
                    "SELECT * FROM " . $this->wpdb->prefix . "terms AS t
                    JOIN " . $this->wpdb->prefix . "term_taxonomy AS tt ON t.term_id = tt.term_id
                    WHERE (name LIKE %s OR slug LIKE %s) AND tt.taxonomy = 'hotel-facilities'",
                    $amenity,
                    str_replace(' ', '-', $amenity)
                );

                $amenity_found_terms = $this->wpdb->get_results($query_terms);

                if ($amenity_found_terms) {
                        $this->insertAmenity($amenity_found_terms[0]->term_taxonomy_id);
                        $amenities[] = $amenity_found_terms[0]->term_taxonomy_id;
                } else
                    $this->createAmenity($amenity, 'hotel-facilities');
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
                "SELECT * FROM " . $this->wpdb->prefix . "terms AS t
                JOIN " . $this->wpdb->prefix . "term_taxonomy AS tt ON t.term_id = tt.term_id
                WHERE (name LIKE %s OR slug LIKE %s) AND tt.taxonomy = 'room-facilities'",
                $amenity,
                str_replace(' ', '-', $amenity)
            );

            $amenity_found_terms = $this->wpdb->get_results($query_terms);

            if ($amenity_found_terms) {
                    $this->insertAmenity($amenity_found_terms[0]->term_taxonomy_id);
                    $amenities[] = $amenity_found_terms[0]->term_taxonomy_id;
            } else
                $this->createAmenity($amenity, 'room-facilities');
        }

    return $amenities;
    }


    public function insertAmenity($hotel_facility_number)
    {
        try {
            $query = $this->wpdb->prepare(
                "INSERT INTO {$this->wpdb->prefix}{$this->table} (object_id, term_taxonomy_id, term_order)
                VALUES (%d, %d, %d)
                ON DUPLICATE KEY UPDATE term_order = VALUES(term_order);",
                $this->post_id, $hotel_facility_number, 0
            );

            $this->wpdb->query($query);

            if ($this->wpdb->last_error) {
                throw new \Exception($this->wpdb->last_error);
            }

            return 'Hotel facility inserted successfully';

        } catch (\Exception $ex) {
            return 'Caught exception: ' . $ex->getMessage();
        }
    }
    

    public function createAmenity($amenity, $type_of_amenity)
    {
    
        echo '<br> Inserting new term <br>';

        $query = $this->wpdb->prepare(
            "INSERT INTO {$this->wpdb->prefix}terms (name, slug, term_group)
            VALUES (%s, %s, %d)",
            str_replace('-', ' ', $amenity), str_replace(' ', '-', $amenity), 0
        );
    
        $this->wpdb->query($query);
    
        if ($this->wpdb->last_error) {
            throw new \Exception($this->wpdb->last_error);
        }
    
        $new_term_id = $this->wpdb->insert_id;
    
        $query = $this->wpdb->prepare(
            "INSERT INTO {$this->wpdb->prefix}term_taxonomy (term_id, taxonomy, description, parent, count)
            VALUES (%d, %s, %s, %d, %d)",
            $new_term_id, $type_of_amenity, '', 0, 0
        );
    
        $this->wpdb->query($query);
    
        if ($this->wpdb->last_error) {
            throw new \Exception($this->wpdb->last_error);
        }
    
        $this->insertAmenity($new_term_id);
    }
    

}


?>
