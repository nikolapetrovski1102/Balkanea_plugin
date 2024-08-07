<?php

    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);

    $path = $_SERVER['DOCUMENT_ROOT']; 

    include_once $path . '/wp-load.php';

    global $wpdb;

    $wpdb->show_errors();
    $prefix = $wpdb->prefix;

    if (isset($_GET['room_parent'])) {

        $room_parent = $_GET['room_parent'];

        $query = $wpdb->prepare("SELECT * FROM " . $prefix . "posts WHERE ID = %s", intval($room_parent));
    
        // Execute the query
        $results = $wpdb->get_row($query);
        
        // Check if results are found
        if (!empty($results)) {
            // Return results as JSON
            return json_encode($results->post_name);
        } else {
            return json_encode(array('error' => 'No results found.'));
        }
    } else {
        return json_encode(array('error' => 'Invalid request.'));
    }

?>