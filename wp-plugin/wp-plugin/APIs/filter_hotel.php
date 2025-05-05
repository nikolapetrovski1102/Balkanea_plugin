<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$path = $_SERVER['DOCUMENT_ROOT'];

error_log($path);

include_once $path . '/wp-load.php';
session_start();

global $wpdb;

if (!isset($_GET['security']) || !wp_verify_nonce($_GET['security'], 'filter_hotel')) {
    http_response_code(403);
    echo json_encode(['error' => 'Invalid nonce: ' . $_GET['security']]);
    exit;
}

$config = include '../config.php';
$apiUrl = 'https://api.worldota.net/api/b2b/v3/search/serp/hotels/';
$keyId = $config['api_key'];
$apiKey = $config['api_password'];

$ch = curl_init($apiUrl);

$wpdb->show_errors();
$prefix = $wpdb->prefix;

$nationality = do_shortcode('[userip_location type="countrycode"]') ?? "MK";

function calculate_request($params) {
    return md5(json_encode($params));
}

function currency_coverter ($currency){
    switch ($currency) {
        case 'USD':
            return 'US$';
        case 'EUR':
            return '€';
        case 'MKD':
            return 'MKD';
        default:
            return '€';
    }
}

if (isset($_GET['data_ids']) && isset($_GET['taxonomy'])){
    
    try{
        $data_ids = $_GET['data_ids'];
        $term_ids = $_GET['taxonomy'];
        
        if (str_contains(',', $term_ids)){
            $term_id_list = implode(',', $term_ids);
            $term_id_count = count($term_ids);
        }
        else{
            $term_id_list = $term_ids;
            $term_id_count = 1;
        }
        
        $all_results = [];
    
        foreach ($data_ids as $hotel_id){
            $query = $wpdb->prepare(
                "SELECT tr.object_id FROM {$prefix}term_relationships AS tr
                LEFT JOIN {$prefix}term_taxonomy AS tt ON tt.term_taxonomy_id = tr.term_taxonomy_id
                WHERE tt.term_id IN ($term_id_list)
                AND tr.object_id = $hotel_id
                GROUP BY tr.object_id
                HAVING COUNT(DISTINCT tt.term_id) = $term_id_count;"
            );
    
            $results = $wpdb->get_results($query);
            
            if (!empty($results)) 
                $all_results = array_merge($all_results, $results);
                
        }
        
        echo json_encode($all_results, true);
    }catch (\Exception $ex){
        error_log($ex->getMessage());
        exit();
    }

}
    
else if ((isset($_GET['ids']) ||  isset($_GET['location_id'])) && isset($_GET['start']) && isset($_GET['end']) && isset($_GET['currency']) ) {

    try{
    
        $params = [
            'location_id' => $_GET['location_id'] ?? "",
            'start' => $_GET['start'],
            'end' => $_GET['end'],
            'currency' => $_GET['currency'],
            'adults' => $_GET['adults'] ?? 1,
            'children' => $_GET['children'] ?? 0
        ];
    
        $request_hash = calculate_request($params);
    
        if (isset($_SESSION['active_request']) && $_SESSION['active_request']['hash'] === $request_hash) {
            while ($_SESSION['active_request']['status'] === 'running') {
                usleep(10000);
            }
    
            if (isset($_SESSION['cached_response'])) {
                echo $_SESSION['cached_response'];
                exit();
            }
        }
    
        $_SESSION['active_request'] = [
            'hash' => $request_hash,
            'status' => 'running'
        ];
    
        $location_id = $_GET['location_id'] ?? "";
        if (isset($_GET['location_id'])) {
        
            $query = $wpdb->prepare(
                "SELECT DISTINCT(post_id) FROM `Y7FXuNUTt_st_location_relationships` WHERE location_from = %d;",
                $location_id
            );
            
            $results = $wpdb->get_results($query);
        
            $post_ids = array_map(function ($result) {
                return $result->post_id;
            }, $results);
        
        }
    
        $ids = rtrim($_GET['ids'], ',') ?? "";
        $checkin = $_GET['start'];
        $checkout = $_GET['end'];
        $adults = intval($_GET['adults']);
        $children = intval($_GET['children']);
        $current_currency_symbol = currency_coverter( $_GET['currency'] );
        $current_currency = $_GET['currency'];
    
        $ids_array = array_map('intval', explode(',', $ids));
    
        $ids_placeholder = implode(',', array_fill(0, count($ids_array), '%d'));
        
        if (!empty($ids)) {
            $ids_array = array_map('intval', explode(',', $ids));
            
            $ids_placeholder = implode(',', array_fill(0, count($ids_array), '%d'));
        
            $query = $wpdb->prepare(
                "SELECT ID, post_name FROM {$wpdb->prefix}posts WHERE ID IN ($ids_placeholder) AND post_author = 6961",
                ...$ids_array
            );
        } else if (!empty($location_id)) {
            $ids_array = $post_ids;
        
            $ids_placeholder = implode(',', array_fill(0, count($ids_array), '%d'));
        
            $query = $wpdb->prepare(
                "SELECT ID, post_name FROM {$wpdb->prefix}posts WHERE ID IN ($ids_placeholder) AND post_author = 6961",
                ...$ids_array
            );
        }
    
        $results = $wpdb->get_results($query);
    
        $dynamic_timout_value = count($results) > 30 ? 30 : ceil(count($results) / 2);
        
        if (empty($results)){
            $_SESSION['cached_response'] = json_encode(array('warning' => 'No results found.'));
            echo json_encode(array('warning' => 'No results found.'));
            $_SESSION['active_request']['status'] = 'completed';
            exit();
        }
    
        if (!empty($results)) {
            
            $all_ids = array_column($results, 'ID');
            
            $post_names = array_map(function($result) {
                return $result->post_name;
            }, $results);
    
            $body_data = array(
                "checkin" => $checkin,
                "checkout" => $checkout,
                "residency" => strtolower($nationality),
                "language" => "en",
                "guests" => array(
                        array(
                            "adults" => $adults,
                            "children" => $children == 0 ? array() : array($children)
                        )
                    ),
                "ids" => $post_names,
                "currency" => $current_currency,
                "timeout" => $dynamic_timout_value
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
    
            $post_names = array_column($results, 'post_name');
            
            $responseData = json_decode($response, true);
            
            if (!isset($responseData) || $responseData['status'] == 'error'){
                error_log("Error during response");
                error_log(print_r($responseData, true));
            }
            
            if (isset($responseData['debug']['validation_error']) && $responseData['debug']['validation_error'] === "checkout cannot be later than 30 days since checkin") {
                error_log("Validation Error: " . $responseData['debug']['validation_error']);
                $_SESSION['cached_response'] = json_encode(["error" => "The checkout date cannot exceed 30 days from the check-in date. Please select a checkout date within this timeframe."]);
                $_SESSION['active_request']['status'] = 'completed';
                echo json_encode(["error" => "The checkout date cannot exceed 30 days from the check-in date. Please select a checkout date within this timeframe."]);
                exit;
            }
            
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
                    foreach ($responseData['data']['hotels'] as $hotel) {
                        if ($hotel['id'] == $result->post_name) {
    
                            $price = round($hotel['rates'][0]['payment_options']['payment_types'][0]['show_amount'] * 1.10);
                            // $price = round($hotel['rates'][0]['daily_prices'][0] * 1.10);
                            
                            $found_ids[$result->ID] = $current_currency_symbol . ' ' . $price;
                            break;
                        }
                    }
                }
            }
    
            $response_call = json_encode([
                'found_ids' => json_encode($found_ids, true),
                'all_ids' => $all_ids
            ]);
    
            if ($response === false){
                error_log(curl_error($ch));
                echo 'Curl error: ' . curl_error($ch);
            }
            else
                $_SESSION['cached_response'] = $response_call;
                echo $response_call;
                
            $_SESSION['active_request']['status'] = 'completed';
            exit();
                
        } else {
            $_SESSION['cached_response'] = json_encode(array('warning' => 'No results found.'));
            echo json_encode(array('warning' => 'No results found.'));
            $_SESSION['active_request']['status'] = 'completed';
            exit();
        }
    }catch (\Exception $ex){
        error_log($ex->getMessage());
        $_SESSION['cached_response'] = json_encode(array('warning' => 'No results found.'));
        echo json_encode(array('warning' => 'No results found.'));
        $_SESSION['active_request']['status'] = 'completed';
        exit();
    }
} else {
    echo json_encode(array('warning' => 'Invalid request.'));
}

?>