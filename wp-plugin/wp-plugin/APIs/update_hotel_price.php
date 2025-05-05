<?php

$path = $_SERVER['DOCUMENT_ROOT']; 
include_once $path . '/wp-load.php';

global $wpdb;
$wpdb->show_errors();
$prefix = $wpdb->prefix;

if (isset($_POST['prices'])) {
    $prices = json_decode(stripslashes($_POST['prices']), true);

    if (is_array($prices)) {
        foreach ($prices as $hotel_id => $hotel_price) {
            $hotel_id = intval($hotel_id);
            $hotel_price = floatval($hotel_price);

            $wpdb->update(
                $prefix . 'postmeta', 
                array('meta_value' => $hotel_price),
                array(
                    'post_id' => $hotel_id, 
                    'meta_key' => 'min_price'
                ), 
                array('%f'),
                array('%d', '%s')
            );
        }

        echo json_encode(array('status' => 'success', 'message' => 'Prices updated successfully.'));
    } else {
        echo json_encode(array('status' => 'error', 'message' => 'Invalid data format.'));
    }
} else {
    echo json_encode(array('status' => 'error', 'message' => 'No data received.'));
}

?>
