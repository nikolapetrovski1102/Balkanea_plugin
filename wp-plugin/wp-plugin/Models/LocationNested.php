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

        echo 'Creating location...<br>';

        $location_exists = $this->locationExists();

        if ($location_exists) {
            echo 'Location exists in DB<br>';
            return $location_exists;
        }

        $this->calculateKeys();

        $this->map_location();

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

        print_r($query);

        $this->wpdb->query($query);

        if ($this->wpdb->last_error)
            throw new \Exception($this->wpdb->last_error);
        else
            return $this->parent_location_id;
    }

    private function locationExists() {
        global $wpdb;
    
        // First query
        $query = $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}{$this->table} WHERE name = %s",
            $this->name
        );
    
        $query_result = $wpdb->get_row($query);
    
        if ($query_result === null)
            return false;
        else{
            return $query_result->location_id;
        }
        // Second query
        // $query = $wpdb->prepare(
        //     "SELECT location_id FROM {$wpdb->prefix}{$this->table} WHERE id = %d",
        //     intval($this->parent_id)
        // );
    
        // $query_result = $wpdb->get_row($query);
    
        // if ($query_result === null) {
        //     echo 'No results found for the second query.<br>';
        //     return null;
        // }
    
        // return $query_result->location_id;
    }
    

    private function map_location() {

        if (!isset($this->parent_id)) {
            throw new Error('Parent ID not set');
        }

        $query = $this->wpdb->prepare(
            "SELECT * FROM {$this->wpdb->prefix}{$this->table} WHERE id = %d",
            $this->parent_id
        );

        $query_result = $this->wpdb->get_row($query);

        if (!$query_result) {
            throw new Error('Parent location not found');
        }

        $this->parent_location_id = $query_result->location_id;
        $this->fullname = $this->name . ', ' . $query_result->name;
    }

    private function mapCountryCode() {

        switch ($this->location_country) {
            case 'GR':
                $this->name = 'Greece';
                $this->fullname = 'Greece';
                break;
            case 'RS':
                $this->name = 'Serbia';
                $this->fullname = 'Serbia';
                break;
            case 'BG':
                $this->name = 'Bulgaria';
                $this->fullname = 'Bulgaria';
                break;
            case 'MK':
                $this->name = 'North Macedonia';
                $this->fullname = 'North Macedonia';
                break;
        }

    }

    public function parentLocationExists(){

        $query = $this->wpdb->prepare(
            "SELECT * FROM {$this->wpdb->prefix}{$this->table} WHERE location_country = %s AND parent_id = 1",
            $this->location_country
        );

        $query_result = $this->wpdb->get_row($query);

        print_r($query);
        echo '<br>' . $query_result->id . '<br>';

        if ($query_result)
            return $query_result->id;
        else{
            $query = $this->wpdb->prepare("
                SELECT * 
                FROM {$this->wpdb->prefix}{$this->table} 
                WHERE parent_id = %d AND status = %s 
                ORDER BY id DESC 
                LIMIT 1", 
                1, 'publish'
            );

            // [id] => 56 [location_id] => 24396 [location_country] => GR [parent_id] => 1 [left_key] => 100 [right_key] => 117 [name] => Greece [fullname] => Greece [language] => en [status] => publish )
            $last_location_added = $this->wpdb->get_row($query); 

            $this->location_id = $last_location_added->location_id + 1;
            $this->parent_id = 1;
            $this->left_key = $last_location_added->right_key + 2;
            $this->right_key = $this->left_key + 1;
            self::mapCountryCode();
            $this->language = 'en';
            $this->status = 'publish';


            $query = $this->wpdb->prepare(
                "INSERT INTO {$this->wpdb->prefix}{$this->table}
            (
                location_id, 
                location_country, 
                parent_id, 
                left_key, 
                right_key, 
                name, 
                fullname, 
                language, 
                status
            ) VALUES (%d, %s, %d, %d, %d, %s, %s, %s, %s)",
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
    
            $query_result = $this->wpdb->get_row($query);

            return $query_result->id;
        }
    }

    private function calculateKeys() {
        $query = $this->wpdb->prepare(
            "SELECT MAX(right_key) AS max_right_key FROM {$this->wpdb->prefix}{$this->table} WHERE parent_id = %d",
            $this->parent_id
        );

        $query_result = $this->wpdb->get_row($query);

        print_r($query_result);

        if (!$query_result || is_null($query_result->max_right_key)) {
            $query = $this->wpdb->prepare(
                "SELECT left_key, right_key FROM {$this->wpdb->prefix}{$this->table} WHERE id = %d",
                $this->parent_id
            );

            echo 'Searching for parent: <br>';
            print_r($query);

            $parent = $this->wpdb->get_row($query);

            if ($parent) {
                $this->left_key = $parent->right_key;
                $this->right_key = $this->left_key + 1;
            } else {
                throw new Error('Unable to determine keys; parent not found');
            }
        } else {
            $this->left_key = $query_result->max_right_key + 1;
            $this->right_key = $this->left_key + 1;
        }

        $this->wpdb->query(
            $this->wpdb->prepare(
                "UPDATE {$this->wpdb->prefix}{$this->table} SET right_key = right_key + 2 WHERE right_key >= %d",
                $this->left_key
            )
        );
    }
}
