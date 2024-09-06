<?php

$apiUrl = 'https://api.worldota.net/api/b2b/v3/hotel/order/booking/form/';
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

if (!isset($_POST['data'])) {
    return 'Error: No data provided.';
}

$data = $_POST['data'];
$price = $data[15]['price'];
$room_id = $data[2]['value'];
$current_ip = $_SERVER['REMOTE_ADDR'];

$price_expode_currency = explode(' ', $price);

switch ($price_expode_currency[0]):
    case '€':
        $price = $price_expode_currency[1];
        break;
    case 'MKD':
        $price = $price_expode_currency[1] / 61.53;
        break;
endswitch;

    try {
        $wpdb->update(
            $prefix . 'postmeta',
            array(
                'meta_value' => $price
            ),
            array(
                'post_id' => $room_id,
                'meta_key' => 'price'
            ),
            array('%f'),
            array('%d', '%s')
        );

        if ($wpdb->last_error)
                throw new \Exception($wpdb->last_error);

    }catch( \Exception $error ){
        echo $error;
    }


$partner_order_id = time();

$cookie_name = "partner_order_id";
$cookie_value = $partner_order_id;

setcookie($cookie_name, $cookie_value, time() + 600, "/", "", true, false);

$body_data = array(
    "partner_order_id" => $partner_order_id,
    "book_hash" => $data[14]['book_hash'],
    "language" => "en",
    "user_ip" => $current_ip
);

curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Authorization: Basic ' . base64_encode("$keyId:$apiKey")
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body_data));

$response = curl_exec($ch);

curl_close($ch);

if ($response === false)
    echo 'Curl error: ' . curl_error($ch);
else
    $response = json_decode($response, true);

    if ($response['status'] == 'ok'){
        setcookie($partner_order_id, json_encode($response['data']['payment_types'][0]), time() + 600, "/", "", true, false);
        echo $response['status'];
    }
    else{
        echo $response['status'];
    }

?>