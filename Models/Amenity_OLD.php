<?php

namespace Models;

class Amenity
{
    private $wpdb;
    private $table = 'term_relationships';

    public array $amenities;
    public string $group_name;
    public int $post_id;

    public function __construct($wpdb)
    {
        $this->wpdb = $wpdb;
    }

    public function getAmenities(): array
    {
        try {
            $amenities = [];
            
            error_log("Processing Amenities");
            
            foreach ($this->amenities['amenities'] as $amenity) {
                error_log("Processing Amenity: $amenity");
                // Normalize common names
                if ($amenity === '24-hour reception') {
                    $amenity = '24-hour front desk';
                } elseif ($amenity === 'Free Wi-Fi') {
                    $amenity = 'Free WiFi';
                }

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
                } else {
                    $this->createAmenity($amenity, 'hotel-facilities');
                }
            }

            return $amenities;

        } catch (\Exception $ex) {
            throw new \Exception('Caught exception in getAmenities: ' . $ex->getMessage());
        }
    }

    public function getRoomAmenities(): array
    {
        try {
            $amenities = [];

            foreach ($this->amenities as $amenity) {

                if ($amenity === '24-hour reception') {
                    $amenity = '24-hour front desk';
                } elseif ($amenity === 'Free Wi-Fi') {
                    $amenity = 'Free WiFi';
                }

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
                } else {
                    $this->createAmenity($amenity, 'room-facilities');
                }
            }

            return $amenities;

        } catch (\Exception $ex) {
            throw new \Exception('Caught exception in getRoomAmenities: ' . $ex->getMessage());
        }
    }

    public function insertAmenity($hotel_facility_number)
    {
        try {
            $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
    
            // Check if amenity already exists
            $exists_query = $this->wpdb->prepare(
                "SELECT COUNT(*) FROM {$this->wpdb->prefix}{$this->table} 
                 WHERE object_id = %d AND term_taxonomy_id = %d",
                $this->post_id,
                $hotel_facility_number
            );
    
            $exists = $this->wpdb->get_var($exists_query);
    
            if ($exists > 0) {
                error_log("Amenity already exists for object_id: {$this->post_id}, term_taxonomy_id: {$hotel_facility_number}");
                return 'Amenity already exists, skipping insert.';
            }
    
            // Only insert if not existing
            $insert_query = $this->wpdb->prepare(
                "INSERT INTO {$this->wpdb->prefix}{$this->table} (object_id, term_taxonomy_id, term_order)
                 VALUES (%d, %d, %d);",
                $this->post_id,
                $hotel_facility_number,
                0
            );
    
            $this->wpdb->query($insert_query);
    
            if ($this->wpdb->last_error) {
                throw new \Exception($this->wpdb->last_error);
            }
    
            return 'Hotel facility inserted successfully';
    
        } catch (\Exception $ex) {
            throw new \Exception('Caught exception in insertAmenity: ' . $ex->getMessage());
        }
    }



    public function createAmenity($amenity, $type_of_amenity)
    {
        try {

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

        } catch (\Exception $ex) {
            throw new \Exception('Caught exception in createAmenity: ' . $ex->getMessage());
        }
    }
}

?>
