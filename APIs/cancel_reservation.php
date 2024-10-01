<?php

$apiUrl = 'https://api.worldota.net/api/b2b/v3/hotel/order/cancel/';
$keyId = '7788';
$apiKey = 'e6a79dc0-c452-48e0-828d-d37614165e39';

$ch = curl_init($apiUrl);

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$path = $_SERVER['DOCUMENT_ROOT']; 

include_once $path . '/wp-load.php';

global $wpdb;

$wpdb->show_errors();
$prefix = $wpdb->prefix;

if (!isset($_POST['order_id']) && !isset($_POST['service_id'])){
    echo 'Invalid request';
    exit();
}

$order_id = $_POST['order_id'];
$service_id = $_POST['service_id'];

echo $order_id;
echo $service_id;

$query = $wpdb->prepare("SELECT * FROM " . $prefix . "postmeta WHERE post_id = %d", $order_id);
$results = $wpdb->get_results($query);

$partner_order_id = $results[58]->meta_value;

$body_data = array(
    'partner_order_id' => $partner_order_id
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

curl_close($ch);

$responseData = json_decode($response, true);

print_r( $responseData['status'] );

if ($responseData['status'] === 'ok'){
    $query = $wpdb->prepare("UPDATE {$prefix}st_order_item_meta SET cancel_refund_status = 'complete', cancel_percent = 0 WHERE order_item_id = %d", $order_id);
    $results = $wpdb->query($query);

    $query = $wpdb->prepare("UPDATE {$prefix}postmeta SET meta_value = 'Cancelled' WHERE post_id = %d AND meta_key = 'status'", $order_id);

}

?>