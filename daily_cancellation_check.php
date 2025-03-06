<?php

$config = include './config.php';

$keyId = $config['api_key'];
$apiKey = $config['api_password'];

    if (!isset($_SERVER['PHP_AUTH_USER']) || !isset($_SERVER['PHP_AUTH_PW'])){
        header('WWW-Authenticate: Basic realm="Restricted Area"');
        header('HTTP/1.0 401 Unauthorized');
        echo 'Authorization header missing';
        exit;
    }
    if ($_SERVER['PHP_AUTH_USER'] !== $keyId || $_SERVER['PHP_AUTH_PW'] !== $apiKey) {
        header('HTTP/1.0 401 Unauthorized');
        header('WWW-Authenticate: Basic realm="Restricted Area"');
        echo 'Unauthorized Access';
        exit;
}

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$path = $_SERVER['DOCUMENT_ROOT'];

include_once $path . '/wp-load.php';

global $wpdb;

$wpdb->show_errors();
$prefix = $wpdb->prefix;

$today = date("d/m/Y");

$query = $wpdb->prepare(
    "SELECT post_id FROM {$prefix}postmeta WHERE meta_key = 'free_cancellation' AND meta_value = %s",
    $today
);
$post_ids = $wpdb->get_col($query);

if (empty($post_ids)) {
    error_log("No bookings found for update.\n");
    exit;
}

foreach ($post_ids as $post_id) {
    $order = wc_get_order($post_id);
    if ($order) {
        if ( $order->has_status('processing') ){
            $order->update_status('completed', 'Free cancellation period ended.');
            error_log("Order ID {$post_id} updated to completed.\n");
        }else{
            error_log("Order ID {$post_id} not payed.\n");
        }
    } else {
        error_log("Order ID {$post_id} not found.\n");
    }
}


?>