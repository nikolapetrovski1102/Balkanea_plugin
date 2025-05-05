<?php
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["error" => "Method Not Allowed. Use POST."]);
    exit;
}

$config = include '../config.php';
$keyId = $config['api_key'];
$apiKey = $config['api_password'];

$apiUrl = 'https://api.worldota.net/api/b2b/v3/search/serp/region/';

// Basic authentication check
// if (!isset($_SERVER['PHP_AUTH_USER']) || !isset($_SERVER['PHP_AUTH_PW'])) {
//     header('WWW-Authenticate: Basic realm="Restricted Area"');
//     header('HTTP/1.0 401 Unauthorized');
//     echo 'Authorization header missing';
//     exit;
// }

// if ($_SERVER['PHP_AUTH_USER'] !== $keyId || $_SERVER['PHP_AUTH_PW'] !== $apiKey) {
//     header('WWW-Authenticate: Basic realm="Restricted Area"');
//     header('HTTP/1.0 401 Unauthorized');
//     echo 'Unauthorized Access';
//     exit;
// }

$data = json_decode(file_get_contents('php://input'), true);

if (isset($data['region_id']) && isset($data['checkin']) && isset($data['checkout']) && isset($data['currency']) && isset($data['adults']) && isset($data['children']) && isset($data['nationality'])) {
    
    $region_id = $data['region_id'];
    $checkin = $data['checkin'];
    $checkout = $data['checkout'];
    $adults = intval($data['adults']);
    $children = intval($data['children']);
    $current_currency = $data['currency'];
    $nationality = $data['nationality'];
    
    $body_data = array(
        "checkin" => $checkin,
        "checkout" => $checkout,
        "residency" => strtolower($nationality), // Assuming $nationality should be defined somewhere
        "language" => "en",
        "guests" => array(
            array(
                "adults" => $adults,
                "children" => $children == 0 ? array() : array($children)
            )
        ),
        "region_id" => $region_id,
        "currency" => $current_currency
    );
 
    $data = json_encode($body_data);

    // Initialize cURL with the correct URL
    $ch = curl_init($apiUrl); // Initialize cURL and set the URL

    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Basic ' . base64_encode("$keyId:$apiKey")
    ]);
    
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    
    $response = curl_exec($ch);
    
    // Check for cURL errors
    if ($response === false) {
        echo json_encode(["error" => "cURL error: " . curl_error($ch)]);
        curl_close($ch);
        exit;
    }

    // Decode the response and send it as JSON
    $responseData = json_decode($response, true);
    curl_close($ch);

    echo json_encode($responseData);
    
}