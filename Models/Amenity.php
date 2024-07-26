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

                $query_terms = $this->wpdb->prepare("SELECT term_id FROM " . $this->wpdb->prefix . "terms WHERE name LIKE %s", '%' . $this->wpdb->esc_like($amenity) . '%');
                $amenity_found_terms = $this->wpdb->get_results($query_terms);

                if ($amenity_found_terms) {
                    foreach ($amenity_found_terms as $term) {
                        $query_term_taxonomy = $this->wpdb->prepare("SELECT term_taxonomy_id FROM " . $this->wpdb->prefix . "term_taxonomy WHERE term_id = %d AND taxonomy = 'hotel-facilities'", $term->term_id);
                        $amenity_found_term_taxonomy = $this->wpdb->get_results($query_term_taxonomy);

                        if ($amenity_found_term_taxonomy) {
                            foreach ($amenity_found_term_taxonomy as $taxonomy) {
                                $this->insertAmenity($taxonomy->term_taxonomy_id);
                                $amenities[] = $taxonomy->term_taxonomy_id;
                            }
                        }
                    }
                }
            }
        }

        return $amenities;
    }

    public function insertAmenity($hotel_facility_number)
    {
        try {
            $this->wpdb->insert(
                $this->wpdb->prefix . $this->table,
                [
                    'object_id' => $this->post_id,
                    'term_taxonomy_id' => $hotel_facility_number,
                    'term_order' => 0
                ]
            );

            return '<br>hotel facility inserted successfully';

        } catch (\Exception $ex) {
            echo 'Caught exception: ', $ex->getMessage(), "\n";
        }
    }
}

?>
