<?php

$keyId = '7788';
$apiKey = 'e6a79dc0-c452-48e0-828d-d37614165e39';

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$path = $_SERVER['DOCUMENT_ROOT']; 

include_once $path . '/wp-load.php';

global $wpdb;

$wpdb->show_errors();
$prefix = $wpdb->prefix;

if (!isset($_POST['data']) && !isset($_POST['type']) ) {
    return 'Error: No data and type provided.';
}

$data = $_POST['data'];
$type = $_POST['type'];

function ConvertCurrency($price_expode_currency){
    switch ($price_expode_currency[0]):
        case '€':
            return $price_expode_currency[1];
        case 'MKD':
            return  $price_expode_currency[1] / 61.53;
    endswitch;
}

function UpdatePrice ($price, $room_id, $prefix, $wpdb) {
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
}

    if ($type === 'order_booking_form'){

        $apiUrl = 'https://api.worldota.net/api/b2b/v3/hotel/order/booking/form/';

        $ch = curl_init($apiUrl);

        $price = $data[15]['price'];
        $room_id = $data[2]['value'];
        $current_ip = $_SERVER['REMOTE_ADDR'];

        $price_expode_currency = explode(' ', $price);

        $price = ConvertCurrency($price_expode_currency);

        UpdatePrice($price, $room_id, $prefix, $wpdb);

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
    }
    else if ($type === 'order_booking_finish'){

        $cleaned_json = stripslashes($data['payment_information']);
        $payment_type_json = json_decode($cleaned_json, true);

        $apiUrl = 'https://api.worldota.net/api/b2b/v3/hotel/order/booking/finish/';

        $ch = curl_init($apiUrl);

        $body_data = array(
            "user" => array(
                'email' => $data['st_email'],
                'comment' => 'TEST',
                'phone' => '+389 71 326 943',
            ),
            "supplier_data" => array(
                "first_name_original" => $data['st_first_name'],
                "last_name_original" => $data['st_last_name'],
                "phone" => $data['st_phone'],
                "email" => $data['st_email']
            ),
            "partner" => array(
                "partner_order_id" => $data['partner_order_id'],
                "comment" => $data['st_note']
            ),
            "language" => "en",
            "rooms" => array(
                array(
                    "guests" => array(
                        array(
                            "first_name" => $data['st_first_name'],
                            "last_name" => $data['st_last_name']
                        )
                    )
                )
            ),
            "payment_type" => $payment_type_json
        );

        $json_result = json_encode($body_data, JSON_PRETTY_PRINT);

        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Basic ' . base64_encode("$keyId:$apiKey")
        ]);
        
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $json_result);

        $response = curl_exec($ch);

        curl_close($ch);

        if ($response === false)
            echo 'Curl error: ' . curl_error($ch);
        else
            $response = json_decode($response, true);

        
        return $response['status'];

    }

?>