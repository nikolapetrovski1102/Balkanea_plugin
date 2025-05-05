<?php

$config = include './config.php';

$keyId = $config['api_key'];
$apiKey = $config['api_password'];

// Check if the Authorization header is set
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

$apiUrl = 'https://api.worldota.net/api/b2b/v3/hotel/info/incremental_dump/';

$ch = curl_init($apiUrl);

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$path = $_SERVER['DOCUMENT_ROOT'];

include_once $path . '/wp-load.php';

global $wpdb;

$wpdb->show_errors();
$prefix = $wpdb->prefix;

$body_data = array(
    'language' => 'en'
);

$data = json_encode($body_data);

curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Authorization: Basic ' . base64_encode("$keyId:$apiKey")
]);

curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $data);

$response = curl_exec($ch);

if ($response === FALSE) {
    die("Failed to fetch .zst file URL");
}

print_r(json_decode($response, true));

?>