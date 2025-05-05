<?php

// Enable error reporting for debugging purposes
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Get the document root path
$path = $_SERVER['DOCUMENT_ROOT']; 

// Include WordPress core file for database access and other functionalities
include_once $path . '/wp-load.php';

// Load configuration settings
$config = include '../config.php';

// Access the global WordPress database object
global $wpdb;

// Enable WordPress database error display
$wpdb->show_errors();
$prefix = $wpdb->prefix;

// Set API credentials
$keyId = $config['api_key'];
$apiKey = $config['api_password'];
$adminEmail = $config['email'];

// Check for security nonce (commented out for now)
// if (!isset($_POST['security']) || !wp_verify_nonce($_POST['security'], 'order_booking_form')) {
//     http_response_code(403);
//     echo json_encode(['error' => 'Invalid nonce: ' . $_POST['security']]);
//     exit;
// }

// Check if data and type are provided
if (!isset($_POST['data']) && !isset($_POST['type']) ) {
    return 'Error: No data and type provided.';
}

// Define error messages for various scenarios
$errorMessages = [
    'block' => 'Card authorization blocked.',
    'charge' => 'Card authorization failed.',
    '3ds' => 'Invalid 3D-secure code.',
    'soldout' => 'The rate is no longer available as rooms at this rate are sold out.',
    'book_limit' => 'Booking failed due to cut-off logic.',
    'provider' => 'Technical error at the rate provider.',
    'order_not_found' => 'No reservation found for the given partner_order_id.',
    'booking_finish_did_not_succeed' => 'Order Booking Finish failed without success.',
    'timeout' => 'Request timed out.',
    'unknown' => 'An unknown error occurred.',
    '5xx' => 'Server error (5xx status code).',
];

// List of errors that should stop the process
$stopErrors = array_keys($errorMessages);

// Retrieve data and type from POST parameters
$data = $_POST['data'];
$type = $_POST['type'];
$current_ip = $_SERVER['REMOTE_ADDR'];

// Function to convert currency based on symbol
function ConvertCurrency($price_expode_currency){
    switch ($price_expode_currency[0]):
        case '€':
            return $price_expode_currency[1];
        case 'MKD':
            return  $price_expode_currency[1] / 61.53;
    endswitch;
}

// Function to update room price in the database
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

        if ($wpdb->last_error){
                error_log("/home/balkanea/public_html/wp-plugin/APIs/order_booking_form.php:52");
                error_log($wpdb->last_error);
                throw new \Exception($wpdb->last_error);
        }

    }catch (\Exception $ex){
        error_log("/home/balkanea/public_html/wp-plugin/APIs/order_booking_form.php:58");
        error_log($ex->getMessage());
        exit();
    }
}

// Function to set order status to failed
function set_order_failed($order_id, $reason) {
    if (!$order_id) {
        return;
    }

    $order = wc_get_order($order_id);
    if ($order) {
        $order->update_status('failed', $reason);
    }
}

function set_order_complete($order_id, $reason) {
    if (!$order_id) {
        return;
    }

    $order = wc_get_order($order_id);
    if ($order) {
        $order->update_status('complete', $reason);
    }
}

// Handle prebook requests
if ($type === 'prebook') {
    $apiUrl = 'https://api.worldota.net/api/b2b/v3/hotel/prebook/';

    $ch = curl_init($apiUrl);

    $body_data = array(
        "hash" => $data,
        "price_increase_percent" => 0
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

    if ($response === false) {
        error_log("Error: " . curl_error($ch));
        echo 'error';
    } else {
        $response = json_decode($response, true);

        if ($response['status'] === 'error') {
            error_log("Error: " . print_r($response, true));
        } else {
            $changes = $response['data']['changes'] ?? false;
            $priceChanged = $changes['price_changed'] ?? false;

            $bookHash = $response['data']['hotels'][0]['rates'][0]['book_hash'] ?? null;

            echo json_encode([
                'price_changed' => $priceChanged ? 'true' : 'false',
                'book_hash' => $bookHash
            ]);
        }
    }
}
// Handle order booking form requests
else if ($type === 'order_booking_form'){

    try{
        
        $apiUrl = 'https://api.worldota.net/api/b2b/v3/hotel/order/booking/form/';

        $ch = curl_init($apiUrl);

        if (!isset($data['price']) && !isset($data['room_id']) &&  !isset($data['book_hash'])){
            error_log("/home/balkanea/public_html/wp-plugin/APIs/order_booking_form.php:Line 120");
            error_log("Data is missing");
            error_log(json_encode($data, true));
            exit();
        }
        
        $price = $data['price'];
        $room_id = $data['room_id'];
        $book_hash = $data['book_hash'];
        $order_data = $data['order_data'];
        
        $price_expode_currency = explode(' ', $price);

        $price = ConvertCurrency($price_expode_currency);

        UpdatePrice($price, $room_id, $prefix, $wpdb);

        $partner_order_id = time();

        $cookie_name = "partner_order_id";
        $cookie_value = $partner_order_id;

        setcookie($cookie_name, $cookie_value, time() + 600, "/", "", true, false);
        setcookie($cookie_value . '_order_data', $order_data, time() + 600, "/", "", true, false);

        $body_data = array(
            "partner_order_id" => $partner_order_id,
            "book_hash" => $book_hash,
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

        // $response = curl_exec($ch);

        curl_close($ch);

        if ($response === false){
            error_log('Curl error: ' . curl_error($ch));
            echo 'error';
        }
        else{
            $response = json_decode($response, true);

            if ($response['status'] == 'ok'){
                setcookie($partner_order_id, json_encode($response['data']['payment_types'][0]), time() + 600, "/", "", true, false);
                echo $response['status'];
            }
            else{
                error_log("/home/balkanea/public_html/wp-plugin/APIs/order_booking_form.php:Line 180");
                error_log(print_r($response, true));
                echo $response['status'];
            }
        }
        }catch (\Exception $ex){
            error_log("/home/balkanea/public_html/wp-plugin/APIs/order_booking_form.php:185");
            error_log($ex->getMessage());
            exit();
        }
}
// Handle order booking finish requests
else if ($type === 'order_booking_finish' && is_numeric($data)) {
    try {
        
        $query = $wpdb->prepare("SELECT * FROM {$wpdb->prefix}postmeta WHERE post_id = %d", $data);
        $datarate = $wpdb->get_results($query);

        $orderData = [];
        foreach ($datarate as $item) {
            $orderData[$item->meta_key] = $item->meta_value;
        }

        if (empty($orderData['partner_order_id'])) {
            set_order_failed($data, "Partner order ID is missing");
            echo json_encode(['status' => 'error', 'message' => 'Partner order ID is missing']);
            exit();
        }

        $order_data_json = $orderData['_order_data'] ?? '';
        $order_data = [];

        if (!empty($order_data_json)) {
            $jsonString = str_replace("\\", "", $order_data_json);
            $order_data = json_decode($jsonString, true);
        }

        $guestNames = [];
        foreach ($order_data as $entry) {
            if ($entry['name'] === 'guest_name[]') {
                $guestNames[] = $entry['value'];
            }
        }

        $guests = [];
        foreach ($guestNames as $name) {
            $nameParts = explode(' ', $name, 2);
            $guests[] = [
                'first_name' => $nameParts[0] ?? '',
                'last_name' => $nameParts[1] ?? ''
            ];
        }

        $cleaned_json = stripslashes($orderData['payment_details']);
        $payment_type_json = json_decode($cleaned_json, true);

        $apiUrl = 'https://api.worldota.net/api/b2b/v3/hotel/order/booking/finish/';
        $ch = curl_init($apiUrl);

        $body_data = [
            "user" => [
                'email' => $adminEmail,
                'comment' => '',
                'phone' => '+389 71 326 943',
            ],
            "supplier_data" => [
                "first_name_original" => $orderData['_billing_first_name'],
                "last_name_original" => $orderData['_billing_last_name'],
                "phone" => $orderData['_billing_phone'],
                "email" => $orderData['_billing_email']
            ],
            "partner" => [
                "partner_order_id" => $orderData['partner_order_id'],
                "comment" => ""
            ],
            "language" => "en",
            "rooms" => [["guests" => $guests]],
            "payment_type" => $payment_type_json
        ];

        $json_result = json_encode($body_data, JSON_PRETTY_PRINT);

        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Basic ' . base64_encode("$keyId:$apiKey")
        ]);
        
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $json_result);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($response === false) {
            set_order_failed($data, "CURL request failed");
            error_log('Curl error: ' . curl_error($ch));
            echo json_encode(['status' => 'error', 'message' => 'CURL request failed']);
            exit();
        }

        $responseData = json_decode($response, true);

        if (!isset($responseData['status']) || $responseData['status'] !== 'ok') {
            set_order_failed($data, "API request failed");
            error_log("API Error: " . print_r($responseData, true) . "with body: " . print_r($json_result, true));
            echo json_encode(['status' => 'error', 'message' => 'API request failed', 'details' => $responseData]);
            exit();
        }

        $apiUrl = 'https://api.worldota.net/api/b2b/v3/hotel/order/booking/finish/status/';
        
        do {
            $ch = curl_init($apiUrl);
            $bodyData = ["partner_order_id" => $orderData['partner_order_id']];
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Authorization: Basic ' . base64_encode("$keyId:$apiKey")
            ]);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($bodyData));

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($response === false) {
                set_order_failed($data, "Status request failed");
                error_log('Curl error: ' . curl_error($ch));
                echo json_encode(['status' => 'error', 'message' => 'Status request failed']);
                exit();
            }

            $responseData = json_decode($response, true);
            $status = $responseData['status'] ?? null;

            if ($status === 'ok') {
                if (!isset($orderData['free_cancellation'])){
                    set_order_complete($data, "");
                };
                error_log("Order #{$data} processed successfully.");
                echo json_encode(['status' => 'success', 'message' => 'Order processed successfully']);
                exit();
            }

            if ($httpCode >= 500) {
                set_order_failed($data, "Server error: HTTP $httpCode");
                error_log("Server error: HTTP $httpCode");
                echo json_encode(['status' => 'error', 'message' => "Server error: HTTP $httpCode"]);
                exit();
            }

            sleep(1);
        } while (true);

    } catch (Exception $e) {
        set_order_failed($data, $e->getMessage());
        error_log("Exception: " . $e->getMessage());
        echo json_encode(['status' => 'error', 'message' => 'Internal error occurred']);
        exit();
    }
    
    }else{
        set_order_failed($data, "Incorrect body");
        error_log("/home/balkanea/public_html/wp-plugin/APIs/order_booking_form.php:Line 334");
        error_log("Incorrect body");
        error_log($data);
    }
    
    exit();

?>