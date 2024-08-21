<?php

    // ini_set('display_errors', 1);
    // ini_set('display_startup_errors', 1);
    // error_reporting(E_ALL);

    $path = $_SERVER['DOCUMENT_ROOT']; 

    include_once $path . '/wp-load.php';

    global $wpdb;

    $wpdb->show_errors();
    $prefix = $wpdb->prefix;

    if (isset($_GET['img_parent_id'])) {

        $post_parent_room_id = $_GET['img_parent_id'];

        $query = $wpdb->prepare("SELECT guid FROM " . $prefix . "posts WHERE post_parent = %s", intval($post_parent_room_id));

        $results = $wpdb->get_results($query);

        if (!empty($results)) {
            echo json_encode($results[1]->guid);
        } else {
            echo json_encode(array('error' => 'No results found.'));
        }

    }
    else if (isset($_GET['post_name'])) {

        $post_name = $_GET['post_name'];

        $query = $wpdb->prepare("SELECT ID, post_parent FROM " . $prefix . "posts WHERE post_name = %s", $post_name);
    
        $results = $wpdb->get_row($query);

        if (!empty($results)) {
            echo json_encode($results);
        } else {
            echo json_encode(array('error' => 'No results found.'));
        }
    }
    else if (isset($_GET['room_parent'])) {

            $room_parent = $_GET['room_parent'];

            $query = $wpdb->prepare("SELECT * FROM " . $prefix . "posts WHERE ID = %s", intval($room_parent));
        
            $results = $wpdb->get_row($query);
            
            if (!empty($results)) {
                echo json_encode($results->post_name);
            } else {
                echo json_encode(array('error' => 'No results found.'));
            }
        } else {
            echo json_encode(array('error' => 'Invalid request.'));
        }

?>