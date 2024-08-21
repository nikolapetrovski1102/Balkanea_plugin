<?php

$apiUrl = 'https://api.worldota.net/api/b2b/v3/search/serp/hotels/';
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

if (isset($_GET['ids']) && isset($_GET['start']) && isset($_GET['end']) ) {

    $ids = rtrim($_GET['ids'], ',');
    $checkin = $_GET['start'];
    $checkout = $_GET['end'];

    $ids_array = array_map('intval', explode(',', $ids));

    $ids_placeholder = implode(',', array_fill(0, count($ids_array), '%d'));

    $query = $wpdb->prepare(
        "SELECT ID, post_name FROM {$prefix}posts WHERE ID IN ($ids_placeholder)", 
        ...$ids_array
    );

    $results = $wpdb->get_results($query);

    if (!empty($results)) {
        $post_names = array_map(function($result) {
            return $result->post_name;
        }, $results);

        $body_data = array(
            "checkin" => $checkin,
            "checkout" => $checkout,
            "residency" => "mk",
            "language" => "en",
            "guests" => array(
                array(
                    "adults" => 2,
                    "children" => array()
                )
            ),
            "ids" => $post_names,
            "currency" => "EUR"
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

        $post_names = array_column($results, 'post_name');
        
        $responseData = json_decode($response, true);
        
        $foundIds = array_map(
            function($hotel) {
                return $hotel['id'];
            },
            array_filter(
                $responseData['data']['hotels'],
                function($hotel) use ($post_names) {
                    return in_array($hotel['id'], $post_names);
                }
            )
        );
        
        $found_ids = [];
        foreach ($results as $result) {
            if (in_array($result->post_name, $foundIds)) {
                $found_ids[] = $result->ID;
            }
        }

        if ($response === false) 
            echo 'Curl error: ' . curl_error($ch);
        else
            echo json_encode( $found_ids );
            
    } else {
        echo json_encode(array('error' => 'No results found.'));
    }
} else {
    echo json_encode(array('error' => 'Invalid request.'));
}

?>
