<?php

namespace Models;

use Error;

class LocationNested {

    private $wpdb;
    private $table = 'st_location_nested';
    private $parent_location_id;
    public $location_id;
    public $location_country;
    public $parent_id;
    public $left_key;
    public $right_key;
    public $name;
    public $fullname;
    public $language;
    public $status;


    public function __construct($wpdb){
        $this->wpdb = $wpdb;
    }

    public function create() {

        $this->map_location();

        echo $this->fullname . '<br>';
        echo $this->location_id . '<br>';

        $query = $this->wpdb->prepare(
            "INSERT INTO {$this->wpdb->prefix}{$this->table} (
                location_id, 
                location_country, 
                parent_id, 
                left_key, 
                right_key, 
                name, 
                fullname, 
                language, 
                status
            ) VALUES (%d, %s, %d, %d, %d, %s, %s, %s, %s)
            ON DUPLICATE KEY UPDATE
                location_country = VALUES(location_country),
                parent_id = VALUES(parent_id),
                left_key = VALUES(left_key),
                right_key = VALUES(right_key),
                name = VALUES(name),
                fullname = VALUES(fullname),
                language = VALUES(language),
                status = VALUES(status)",
            $this->location_id, 
            $this->location_country, 
            $this->parent_id, 
            $this->left_key, 
            $this->right_key, 
            $this->name, 
            $this->fullname, 
            $this->language, 
            $this->status
        );

        $this->wpdb->query($query);

        if ($this->wpdb->last_error)
                throw new \Exception($this->wpdb->last_error);
            else
                return $this->parent_location_id;

    }


    private function map_location (){

        if (!isset($this->parent_id)){
            return new Error('Parent ID not set');
        }

        $query = $this->wpdb->prepare(
            "SELECT * FROM {$this->wpdb->prefix}{$this->table} WHERE id = %d",
            $this->parent_id
        );

        $query_result = $this->wpdb->get_row($query);

        $parent_name = $query_result->name;
        $this->parent_location_id = $query_result->location_id;

        $this->fullname = $this->name . ', ' . $parent_name;

    }

}

?>