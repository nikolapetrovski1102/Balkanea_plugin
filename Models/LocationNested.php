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

        $location_exists = $this->locationExists();

        if ($location_exists) {
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

        $this->wpdb->query($query);

        if ($this->wpdb->last_error)
            throw new \Exception($this->wpdb->last_error);
        else
            return $this->parent_location_id;
    }

    private function locationExists() {

        $query = $this->wpdb->prepare(
            "SELECT * FROM {$this->wpdb->prefix}{$this->table} WHERE name = %s",
            $this->name
        );

        $query_result = $this->wpdb->get_row($query);

        $query = $this->wpdb->prepare(
            "SELECT location_id FROM {$this->wpdb->prefix}{$this->table} WHERE id = %d",
            intval($this->parent_id)
        );

        $query_result = $this->wpdb->get_row($query);

        return $query_result->location_id;
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

    private function calculateKeys() {
        $query = $this->wpdb->prepare(
            "SELECT MAX(right_key) AS max_right_key FROM {$this->wpdb->prefix}{$this->table} WHERE parent_id = %d",
            $this->parent_id
        );

        $query_result = $this->wpdb->get_row($query);

        if (!$query_result || is_null($query_result->max_right_key)) {
            $query = $this->wpdb->prepare(
                "SELECT left_key, right_key FROM {$this->wpdb->prefix}{$this->table} WHERE location_id = %d",
                $this->parent_id
            );
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
