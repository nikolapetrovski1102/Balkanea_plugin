<?php

    // ini_set('display_errors', 1);
    // ini_set('display_startup_errors', 1);
    // error_reporting(E_ALL);

$path = $_SERVER['DOCUMENT_ROOT']; 

include_once $path . '/wp-load.php';

global $wpdb;

$wpdb->show_errors();
$prefix = $wpdb->prefix;

if (!isset($_GET['security']) || !wp_verify_nonce($_GET['security'], 'query')) {
    http_response_code(403);
    echo json_encode(['error' => 'Invalid nonce: ' . $_GET['security']]);
    exit;
}

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
    else if (isset($_GET['data_order_id']) && isset($_GET['partner_timestamp']) && isset($_GET['free_cancelation'])) {

        $data_order_id = $_GET['data_order_id'];
        $partner_timestamp = $_GET['partner_timestamp'];
        $free_cancelation = $_GET['free_cancelation'];

        $query = $wpdb->prepare("INSERT INTO " . $prefix . "postmeta (post_id, meta_key, meta_value) VALUES (%d, %s, %s), (%d, %s, %s)", $data_order_id, 'partner_order_id', $partner_timestamp, $data_order_id, 'free_cancelation_before', $free_cancelation);

        $results = $wpdb->query($query);
    }
    else if (isset($_GET['hotel_id'])) {
        
        $hotel_id = $_GET['hotel_id'];
        
        $metapolicy_struct = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT meta_value FROM {$wpdb->prefix}postmeta WHERE post_id = %d AND meta_key = 'metapolicy_struct';", $hotel_id
            )
        );
        
        if (!empty($metapolicy_struct)){
            echo json_encode($metapolicy_struct);
        }
        
    }
    else {
        echo json_encode(array('error' => 'Invalid request.'));
    }

?>