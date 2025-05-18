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

        $this->map_location();
        
        $this->calculateKeys();

        $this->fixOverlapingLocations();
        
        if ($this->wpdb->last_error)
            throw new \Exception($this->wpdb->last_error);
        else
            return $this->parent_location_id;
    }

    private function locationExists() {
        global $wpdb;
    
        // First query
        $query = $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}{$this->table} WHERE name = %s AND location_country = %s",
            $this->name,
            $this->location_country
        );
    
        $query_result = $wpdb->get_row($query);
    
        if ($query_result === null)
            return false;
        else{
            return $query_result->location_id;
        }
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
        if (!function_exists('add_action')) {
            require_once('/home/balkanea/public_html/wp-load.php');
        }
    
        if (function_exists('WC')) {
            $this->name = WC()->countries->countries[$this->location_country] ?? 'Unknown';
            $this->fullname = $this->name;
        } else {
            $this->name = 'Unknown';
            $this->fullname = 'Unknown';
        }
    }

    public function parentLocationExists() {
        $query = $this->wpdb->prepare(
            "SELECT * FROM {$this->wpdb->prefix}{$this->table} WHERE location_country = %s AND parent_id = 1",
            $this->location_country
        );
    
        $query_result = $this->wpdb->get_row($query);
    
        if ($query_result) {
            $row_array = (array) $query_result;
            return json_encode([
                "ID" => $row_array['id'],
                "location_id" => $row_array['location_id']
            ]);
        } else {
            $query = $this->wpdb->prepare("
                SELECT * 
                FROM {$this->wpdb->prefix}{$this->table} 
                WHERE parent_id = %d AND status = %s 
                ORDER BY right_key DESC 
                LIMIT 1",
                1, 'publish'
            );
    
            $last_location_added = $this->wpdb->get_row($query);
    
            $this->location_id = $last_location_added->location_id + 1;
            $this->parent_id = 1;
            $this->left_key = $last_location_added->right_key + 2;
            $this->right_key = $this->left_key + 1;
            $this->mapCountryCode();
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
    
            $this->wpdb->query($query);
            $inserted_id = $this->wpdb->insert_id;
    
            return json_encode([
                "ID" => $inserted_id,
                "location_id" => $this->location_id
            ]);
        }
    }

    private function calculateKeys() {
        // Begin transaction to ensure data integrity
        $this->wpdb->query('START TRANSACTION');
        
        try {
            // Retrieve the parent's current left and right keys
            $parent_query = $this->wpdb->prepare(
                "SELECT MAX(right_key) AS key_indicator FROM {$this->wpdb->prefix}{$this->table} WHERE id = %d",
                $this->parent_id
            );
            $parent = $this->wpdb->get_row($parent_query);
            
            if (!$parent) {
                throw new \Exception('Unable to determine keys; parent not found');
            }
            
            // The new node will be placed inside the parent, just before the parent's right boundary
            $this->left_key = $parent->key_indicator;
            $this->right_key = $parent->key_indicator + 1;
            
            // Adding the new location
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
            
            // Execute it!
            $this->wpdb->query($query);
            
            // First, make space for the new node by shifting all right keys >= parent's right_key - 1
            $this->wpdb->query(
                $this->wpdb->prepare(
                    "UPDATE {$this->wpdb->prefix}{$this->table} SET right_key = right_key + 2 WHERE id = %d",
                    $this->parent_id
                )
            );
            
            // Commit the transaction
            $this->wpdb->query('COMMIT');
            
        } catch (\Exception $e) {
            // Roll back on error
            $this->wpdb->query('ROLLBACK');
            throw $e;
        }
    }
    
    public function fixOverlapingLocations () {
        try{
            $wpdb = $this->wpdb;
            $table = $wpdb->prefix . 'st_location_nested';
            $parentId = 1;
            
            /* 
             * Step 1: Find overlapping parent nodes under the same parent, 
             * Ex If greece is from 102-157 and the next country start from 157 all next countries and their children must be shifted
            */
            $nodes = $wpdb->get_results("
                SELECT 
                    DISTINCT a.*
                FROM $table a
                JOIN $table b ON a.id != b.id
                WHERE 
                    a.parent_id = $parentId AND b.parent_id = $parentId AND (
                        a.left_key BETWEEN b.left_key AND b.right_key
                        OR a.right_key BETWEEN b.left_key AND b.right_key
                    )
                    AND a.status = 'publish' AND b.status = 'publish'
                ORDER BY a.left_key ASC
            ");
            
            if (count($nodes) == 0)
                return;
            
            for ($i = 0; $i < count($nodes) - 1; $i++) {
                $current = $nodes[$i];
                $next = $nodes[$i + 1];
            
                // Only shift if overlapping
                if ($current->right_key >= $next->left_key) {
                    $shiftAmount = $current->right_key - $next->left_key + 1;
            
                    // Update DB
                    $wpdb->query(
                        $wpdb->prepare(
                            "UPDATE $table
                             SET left_key = left_key + %d,
                                 right_key = right_key + %d
                             WHERE id = %d",
                            $shiftAmount, $shiftAmount, $next->id
                        )
                    );
                    
                    $wpdb->query(
                        $wpdb->prepare(
                            "UPDATE $table
                             SET left_key = left_key + %d,
                                 right_key = right_key + %d
                             WHERE parent_id = %d",
                            $shiftAmount, $shiftAmount, $next->id
                        )
                    );
            
                    // Update the in-memory object so the next loop uses fresh keys
                    $nodes[$i + 1]->left_key += $shiftAmount;
                    $nodes[$i + 1]->right_key += $shiftAmount;
                }
            }
            
            // Updating last node!!
            $lastNode = $nodes[count($nodes)];
            $beforeLastNode = $nodes[count($nodes) - 1];
            
            $shiftAmount = $beforeLastNode->right_key - $lastNode->left_key + 1;
            
            $wpdb->query(
                $wpdb->prepare(
                    "UPDATE $table
                     SET left_key = left_key + %d,
                         right_key = right_key + %d
                     WHERE id = %d",
                    $shiftAmount, $shiftAmount, $lastNode->id
                )
            );
            
            $wpdb->query(
                $wpdb->prepare(
                    "UPDATE $table
                     SET left_key = left_key + %d,
                         right_key = right_key + %d
                     WHERE parent_id = %d",
                    $shiftAmount, $shiftAmount, $lastNode->id
                )
            );
        }catch (\Exception $e) {
            throw $e;
        }

    }
    
}
