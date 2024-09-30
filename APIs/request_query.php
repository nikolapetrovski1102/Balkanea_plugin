<?php

    // ini_set('display_errors', 1);
    // ini_set('display_startup_errors', 1);
    // error_reporting(E_ALL);

    $path = $_SERVER['DOCUMENT_ROOT']; 

    include_once $path . '/wp-load.php';

    global $wpdb;

    $wpdb->show_errors();
    $prefix = $wpdb->prefix;

if (isset($_GET['room_parent'])) {

        $room_parent = $_GET['room_parent'];

        $query = $wpdb->prepare("SELECT * FROM " . $prefix . "posts WHERE ID = %s", intval($room_parent));
    
        $results = $wpdb->get_row($query);
        
        if (!empty($results)) {
            echo json_encode($results->post_name);
        } else {
            echo json_encode(array('error' => 'No results found.'));
        }
    }
    else if (isset($_GET['data_order_id']) && (isset($_GET['partner_timestamp']))) {

        $data_order_id = $_GET['data_order_id'];
        $partner_timestamp = $_GET['partner_timestamp'];

        $query = $wpdb->prepare("INSERT INTO " . $prefix . "postmeta (post_id, meta_key, meta_value) VALUES (%d, %s, %s)", $data_order_id, 'partner_order_id', $partner_timestamp);

        $results = $wpdb->query($query);
    }else {
        echo json_encode(array('error' => 'Invalid request.'));
    }

?>