<?php

namespace Models;

use Log;

class PostsHotel
{
    private $wpdb;
    private $table = 'posts';

    public $id;
    public $post_author;
    public $post_date;
    public $post_date_gmt;
    public $post_content;
    public $post_title;
    public $post_excerpt;
    public $post_status;
    public $comment_status;
    public $ping_status;
    public $post_password;
    public $post_name;
    public $to_ping;
    public $pinged;
    public $post_modified;
    public $post_modified_gmt;
    public $post_content_filtered;
    public $post_parent;
    public $guid;
    public $menu_order;
    public $post_type;
    public $post_mime_type;
    public $comment_count;
    private Log $log;

    public function __construct($wpdb, Log $log)
    {
        $this->log = $log;
        $this->wpdb = $wpdb;
        $this->post_author = 6961;
        $this->comment_status = 'open';
        $this->ping_status = 'open';
        $this->post_parent = 0;
        $this->menu_order = 0;
        $this->post_type = 'st_hotel';
        $this->comment_count = 0;
    }

    public function isModified() {
        if (!is_string($this->post_name)) {
            $this->log->error($this->post_name);
            throw new \Exception("post_name must be a string");
        }

        $query = $this->wpdb->prepare(
            "SELECT post_modified FROM " . $this->wpdb->prefix . $this->table . " WHERE post_name = %s",
            $this->post_name
        );
        $result = $this->wpdb->get_row($query);

        //error_log(print_r($result, true));

        if ($result) {
            $post_modified = $result->post_modified;
            $modified_date = date('Y-m-d', strtotime($post_modified)); // Extract date part of post_modified
            $today_date = date('Y-m-d'); // Get today's date

            if ($modified_date === $today_date) {
                return true;
            }
        }

        return false;
    }


    // Create Post
    public function create()
    {
        $hotel_id = $this->hotelExists();
        if ($hotel_id) {
            $this->log->info("found id: $hotel_id");
            return $hotel_id;
        }

        $data = [
            'post_author' => $this->post_author,
            'post_date' => date('Y-m-d H:i:s'),
            'post_date_gmt' => date('Y-m-d H:i:s'),
            'post_content' => $this->post_content,
            'post_title' => $this->post_title,
            'post_excerpt' => $this->post_excerpt,
            'post_status' => $this->post_status,
            'comment_status' => $this->comment_status,
            'ping_status' => $this->ping_status,
            'post_password' => $this->post_password,
            'post_name' => $this->post_name,
            'to_ping' => $this->to_ping,
            'pinged' => $this->pinged,
            'post_modified' => date('Y-m-d H:i:s'),
            'post_modified_gmt' => date('Y-m-d H:i:s'),
            'post_content_filtered' => $this->post_content_filtered,
            'post_parent' => $this->post_parent,
            'guid' => $this->guid,
            'menu_order' => $this->menu_order,
            'post_type' => $this->post_type,
            'post_mime_type' => $this->post_mime_type,
            'comment_count' => $this->comment_count,
        ];

        $format = [
            '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s',
            '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%s', '%s', '%d'
        ];

        try{
            $result = $this->wpdb->insert($this->wpdb->prefix . $this->table, $data, $format);
            $hotelPostId = $this->wpdb->insert_id;
            if ($this->wpdb->last_error) {
                $this->log->error($this->wpdb->last_error);
                throw new \Exception($this->wpdb->last_error);
            }
            else {
                $this->log->info("Create hotel in post table: " . $hotelPostId);

                return $this->wpdb->insert_id;
            }

        }catch(\Exception $ex){
            $this->log->error('Caught exception: ' .  $ex->getMessage() . $ex->getTraceAsString());
            return 'Caught exception: ' .  $ex->getMessage() . "\n";
        }
    }

    // Read Posts
    public function read()
    {
        $query = "SELECT * FROM " . $this->wpdb->prefix . $this->table;
        return $this->wpdb->get_results($query);
    }

    // Update Post
    public function update()
    {
        $this->log->info("Update: " . $this->id);
        $data = [
            'post_author' => $this->post_author,
            'post_content' => $this->post_content,
            'post_title' => $this->post_title,
            'post_excerpt' => $this->post_excerpt,
            'post_status' => $this->post_status,
            'comment_status' => $this->comment_status,
            'ping_status' => $this->ping_status,
            'post_password' => $this->post_password,
            'post_name' => $this->post_name,
            'to_ping' => $this->to_ping,
            'pinged' => $this->pinged,
            'post_modified' => date('Y-m-d H:i:s'),
            'post_modified_gmt' => date('Y-m-d H:i:s'),
            'post_content_filtered' => $this->post_content_filtered,
            'post_parent' => $this->post_parent,
            'guid' => $this->guid,
            'menu_order' => $this->menu_order,
            'post_type' => $this->post_type,
            'post_mime_type' => $this->post_mime_type,
            'comment_count' => $this->comment_count,
        ];

        $where = ['ID' => $this->id];
        $format = [
            '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s',
            '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%s', '%s', '%d'
        ];
        $where_format = ['%d'];

        $result = $this->wpdb->update($this->wpdb->prefix . $this->table, $data, $where, $format, $where_format);

        return $this->id;
    }

    // Delete Post
    public function delete()
    {
        $where = ['ID' => $this->id];
        $where_format = ['%d'];

        $result = $this->wpdb->delete($this->wpdb->prefix . $this->table, $where, $where_format);

        return $result !== false;
    }

    public function get()
    {
        error_log("[INFO] Get");

        if (!is_string($this->post_name)) {
            throw new \Exception("post_title must be a string");
        }

        $query = $this->wpdb->prepare("SELECT * FROM " . $this->wpdb->prefix . $this->table . " WHERE post_name = %s", $this->post_name);
        $result = $this->wpdb->get_row($query);

        return $result != null ? $result->ID : NULL;
    }

    private function hotelExists(){

        if (!is_string($this->post_name)) {
            throw new \Exception("post_title must be a string");
        }

        $query = $this->wpdb->prepare("SELECT * FROM " . $this->wpdb->prefix . $this->table . " WHERE post_name = %s", $this->post_name);
        $result = $this->wpdb->get_row($query);

        if ($result)
            return $result != null ? $result->ID : $result;
    }

}
?>
