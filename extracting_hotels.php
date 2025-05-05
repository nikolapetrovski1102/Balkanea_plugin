/**
 * Hotel Data Extraction Script
 * 
 * This script connects to the WorldOta API to fetch incremental hotel data dumps.
 * It retrieves compressed (.zst) files containing updated hotel information
 * that can be used to synchronize the local database with the latest hotel data.
 */
<?php

// Load configuration and API credentials
$config = include './config.php';
$keyId = $config['api_key'];
$apiKey = $config['api_password'];

// Verify API authentication credentials
if (!isset($_SERVER['PHP_AUTH_USER']) || !isset($_SERVER['PHP_AUTH_PW'])){
    header('WWW-Authenticate: Basic realm="Restricted Area"');
    header('HTTP/1.0 401 Unauthorized');
    echo 'Authorization header missing';
    exit;
}
if ($_SERVER['PHP_AUTH_USER'] !== $keyId || $_SERVER['PHP_AUTH_PW'] !== $apiKey) {
    header('WWW-Authenticate: Basic realm="Restricted Area"');
    header('HTTP/1.0 401 Unauthorized');
    echo 'Unauthorized Access';
    exit;
}

// WorldOta API endpoint for incremental hotel data dumps
$apiUrl = 'https://api.worldota.net/api/b2b/v3/hotel/info/incremental_dump/';

// Initialize cURL session
$ch = curl_init($apiUrl);

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

// Prepare request body with language preference
$body_data = array(
    'language' => 'en'
);

$data = json_encode($body_data);

// Set up cURL options with authentication and content headers
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Authorization: Basic ' . base64_encode("$keyId:$apiKey")
]);

// Configure cURL request parameters
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $data);

// Execute the API request
$response = curl_exec($ch);

// Check for request failure
if ($response === FALSE) {
    die("Failed to fetch .zst file URL");
}

// Output the decoded response data
print_r(json_decode($response, true));

?>