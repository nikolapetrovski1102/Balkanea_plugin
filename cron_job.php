<?php

//require_once 'vendor/autoload.php';
//use JMS\Serializer\SerializerBuilder;
use Models\RootObject;

    global $wpdb;

    $prefix = 'Y7FXuNUTt';
    $url = 'https://api.worldota.net/api/b2b/v3/hotel/info/?data={"id":"beach_studio_apartment_3_komi","language":"en"}';
    $keyId = '7788';
    $apiKey = 'e6a79dc0-c452-48e0-828d-d37614165e39';

    $credentials = base64_encode($keyId . ':' . $apiKey);
    $headers = array(
        'Authorization: Basic ' . $credentials,
    );

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Disable SSL verification

    $response = curl_exec($ch);
 
    $data2 = json_decode($response, true);

    print_r($data2);

    $res = $wpdb->insert(
        $prefix . '_posts',
        array(
            'post_author' => 6,
            'post_date' => '2024-04-26 14:01:44',
            'post_date_gmt' => '2024-04-26 14:01:44',
            'post_content' => 'TEST TITLE',
            'post_title' => 'TEST TITLE',
            'post_excerpt' => 'TEST TITLE',
            'post_status' => 'draft',
            'comment_status' => 'open',
            'ping_status' => 'open',
            'post_password' => '',
            'post_name' => '',
            'to_ping' => '',
            'pinged' => '',
            'post_modified' => '2024-04-26 14:01:44',
            'post_modified_gmt' => '2024-04-26 14:01:44',
            'post_content_filtered' => '',
            'post_parent' => 0,
            'guid' => '',
            'menu_order' => 0,
            'post_type' => 'post',
            'post_mime_type' => '',
            'comment_count' => 0
        )
    );
    
    print_r($res);

    curl_close($ch);

?>