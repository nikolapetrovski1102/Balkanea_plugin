<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$path = $_SERVER['DOCUMENT_ROOT']; 

include_once $path . '/wp-load.php';

global $wpdb;

function get_client_ip() {
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        return $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        return explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0]; // First IP in list
    } else {
        return $_SERVER['REMOTE_ADDR'];
    }
}

$ip_address = get_client_ip();

if ($ip_address != '62.77.140.21'){
    http_response_code(403);
    echo json_encode(['error' => 'You do not have access']);
    exit;
}

// if (!isset($_POST['security']) || !wp_verify_nonce($_POST['security'], 'cancel_order')) {
//     http_response_code(403);
//     echo json_encode(['error' => 'Invalid nonce cancel: ' . $_POST['security']]);
//     exit;
// }

$wpdb->show_errors();
$prefix = $wpdb->prefix;

if (!isset($_POST['order_id'])){
    error_log("Invalid request");
    echo 'Invalid request';
    exit();
}

$config = include '../config.php';
$apiUrl = 'https://api.worldota.net/api/b2b/v3/hotel/order/cancel/';
$keyId = $config['api_key'];
$apiKey = $config['api_password'];

$ch = curl_init($apiUrl);

try{
    $order_id = $_POST['order_id'];
    
    $partner_order_id = $wpdb->get_var(
        $wpdb->prepare(
            "SELECT meta_value FROM {$wpdb->prefix}postmeta WHERE post_id = %d AND meta_key = %s",
            $order_id,
            'partner_order_id'
        )
    );
    
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
    
    if(curl_errno($ch)) {
        error_log('Curl error: ' . print_r(curl_error($ch), true));
    }else{
        $responseData = json_decode($response, true);
        echo $responseData['status'];
    }
    
    curl_close($ch);
    
}catch (\Exception $ex){
    error_log($ex->getMessage());
}
?>