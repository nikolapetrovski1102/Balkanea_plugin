/**
 * Daily Cancellation Check Script
 * 
 * This script checks for hotel bookings whose free cancellation period ends today
 * and updates their status accordingly in the WordPress/WooCommerce system.
 * It runs daily to manage the transition of orders from processing to completed
 * once their cancellation period has expired.
 */
<?php

// Load configuration and API credentials
$config = include './config.php';
$keyId = $config['api_key'];
$apiKey = $config['api_password'];

    // Basic authentication check
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

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Load WordPress core
$path = $_SERVER['DOCUMENT_ROOT'];
include_once $path . '/wp-load.php';

// Initialize WordPress database connection
global $wpdb;
$wpdb->show_errors();
$prefix = $wpdb->prefix;

// Get today's date in the required format (dd/mm/yyyy)
$today = date("d/m/Y");

// Query to find all orders where free cancellation expires today
$query = $wpdb->prepare(
    "SELECT post_id FROM {$prefix}postmeta WHERE meta_key = 'free_cancellation' AND meta_value = %s",
    $today
);
$post_ids = $wpdb->get_col($query);

// Exit if no bookings need to be updated
if (empty($post_ids)) {
    error_log("No bookings found for update.\n");
    exit;
}

// Process each order that needs updating
foreach ($post_ids as $post_id) {
    $order = wc_get_order($post_id);
    if ($order) {
        // Only update orders that are in 'processing' status
        if ( $order->has_status('processing') ){
            // Mark the order as completed since cancellation period has ended
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