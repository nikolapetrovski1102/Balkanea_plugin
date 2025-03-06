<?php

namespace Models;

class LocationRelationship
{
    public $post_id;
    public $location_from;
    public $location_to;
    public $post_type;
    public $location_type;
    
    private $wpdb;
    private $table = 'st_location_relationships';

    public function __construct($wpdb)
    {
        $this->wpdb = $wpdb;
    }

    public function insertLocationRelationship()
    {
        foreach ($this->location_from as $location) {

            try {
                $query = $this->wpdb->prepare(
                    "INSERT INTO {$this->wpdb->prefix}{$this->table} (post_id, location_from, location_to, post_type, location_type) 
                VALUES (%d, %s, %s, %s, %s)",
                $this->post_id, $location, $this->location_to, $this->post_type, $this->location_type
                );

                $this->wpdb->query($query);
                
                if ($this->wpdb->last_error) {
                    throw new \Exception($this->wpdb->last_error);
                }
                            
            } catch (\Exception $ex) {
                return 'Caught exception: ' . $ex->getMessage() . "\n";
            }
            
        }

        return 'Location relationship inserted successfully';

    }
}
