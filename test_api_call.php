<?php

    $config = include './config.php';
    $keyId = $config['api_key'];
    $apiKey = $config['api_password'];
    
    $credentials = base64_encode($keyId . ':' . $apiKey);
    
    $headers = array(
        'Authorization: Basic ' . $credentials
    );

    $url = 'https://api.worldota.net/api/b2b/v3/hotel/info/';

    error_log("Executing hotel" . $hotel_id);

    $data_hotel = array(
        'id' => 'zappion_hotel',
        'language' => 'mk'                // Set response language to English
    );

    $json_data_hotel = json_encode($data_hotel);
    
    $ci = curl_init();
    
    curl_setopt($ci, CURLOPT_URL, $url);
    curl_setopt($ci, CURLOPT_POST, 1);
    curl_setopt($ci, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ci, CURLOPT_POSTFIELDS, $json_data_hotel);
    curl_setopt($ci, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ci, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ci, CURLOPT_SSL_VERIFYPEER, false);
    
    $response = curl_exec($ci);

    curl_close($ci);

    if ($response === false)
        return curl_error($ci);
    else
        echo print_r(json_decode($response, true), JSON_PRETTY_PRINT);

// Load WordPress
// require_once( dirname(__FILE__, 2) . '/wp-load.php' );

// Now you can safely use WooCommerce functions
// echo WC()->countries->countries['AU']; // Outputs "Australia"


// $path = $_SERVER['DOCUMENT_ROOT']; 

// include_once $path . '/wp-load.php';

// global $wpdb;

// $wpdb->show_errors();

// $table = $wpdb->prefix . 'st_location_nested';
// $parentId = 1;

// // Step 1: Find overlapping parent nodes under the same parent
// $nodes = $wpdb->get_results("
//     SELECT 
//         DISTINCT a.*
//     FROM $table a
//     JOIN $table b ON a.id != b.id
//     WHERE 
//         a.parent_id = $parentId AND b.parent_id = $parentId AND (
//             a.left_key BETWEEN b.left_key AND b.right_key
//             OR a.right_key BETWEEN b.left_key AND b.right_key
//         )
//         AND a.status = 'publish' AND b.status = 'publish'
//     ORDER BY a.left_key ASC
// ");


// // echo print_r($overlapping) . "<br>";
// // $first_region = array_splice($overlapping, 0, 1);
// // echo print_r($nodes) . "<br> <br>";

// for ($i = 0; $i < count($nodes) - 1; $i++) {
//     $current = $nodes[$i];
//     $next = $nodes[$i + 1];

//     // Only shift if overlapping
//     if ($current->right_key >= $next->left_key) {
//         $shiftAmount = $current->right_key - $next->left_key + 1;

//         // Update DB
//         $wpdb->query(
//             $wpdb->prepare(
//                 "UPDATE $table
//                  SET left_key = left_key + %d,
//                      right_key = right_key + %d
//                  WHERE id = %d",
//                 $shiftAmount, $shiftAmount, $next->id
//             )
//         );
        
//         $wpdb->query(
//             $wpdb->prepare(
//                 "UPDATE $table
//                  SET left_key = left_key + %d,
//                      right_key = right_key + %d
//                  WHERE parent_id = %d",
//                 $shiftAmount, $shiftAmount, $next->id
//             )
//         );

//         // Update the in-memory object so the next loop uses fresh keys
//         $nodes[$i + 1]->left_key += $shiftAmount;
//         $nodes[$i + 1]->right_key += $shiftAmount;
//     }
// }

// echo "<br>Shift amount LAST: $shiftAmount <br>";

// // Updating last node!!
// $lastNode = $nodes[count($nodes)];
// $beforeLastNode = $nodes[count($nodes) - 1];

// echo 'Last node: ' . print_r($lastNode);
// echo '<br> Before Last node: ' . print_r($beforeLastNode);

// $shiftAmount = $beforeLastNode->right_key - $lastNode->left_key + 1;

// $wpdb->query(
//     $wpdb->prepare(
//         "UPDATE $table
//          SET left_key = left_key + %d,
//              right_key = right_key + %d
//          WHERE id = %d",
//         $shiftAmount, $shiftAmount, $lastNode->id
//     )
// );

// $wpdb->query(
//     $wpdb->prepare(
//         "UPDATE $table
//          SET left_key = left_key + %d,
//              right_key = right_key + %d
//          WHERE parent_id = %d",
//         $shiftAmount, $shiftAmount, $lastNode->id
//     )
// );

// Step 2: Loop through detected overlaps
// foreach ($overlapping as $pair) {
    
//     // echo print_r($pair) . "<br>";
    
//     $aId = $pair->a_id;
//     $bId = $pair->b_id;

//     $aLeft = $pair->a_left;
//     $aRight = $pair->a_right;
//     $bLeft = $pair->b_left;
//     $bRight = $pair->b_right;

//     // // Determine which node is overlapping (the one starting later)
//     if ($bLeft >= $aLeft && $bLeft <= $aRight) {
//         // Node B is overlapping node A
//         $shiftAmount = ($aRight - $bLeft + 1);

//         // Step 3: Shift node B and all its children
//         $wpdb->query(
//             $wpdb->prepare(
//                 "UPDATE $table
//                  SET left_key = left_key + %d,
//                      right_key = right_key + %d
//                  WHERE left_key >= %d AND right_key <= %d",
//                 $shiftAmount, $shiftAmount, $bLeft, $bRight
//             )
//         );

//         // Optional: log or echo what happened
//         echo "Shifted node ID $bId and its children by $shiftAmount<br>";
//     }
// }


// $config = include './config.php';
// $keyId = $config['api_key'];
// $apiKey = $config['api_password'];

// $credentials = base64_encode($keyId . ':' . $apiKey);

// $headers = array(
//     'Authorization: Basic ' . $credentials
// );

// $data = array(
//     "region_id" => 'premier_luxury_mountain_resort',
//     "country_code" => "en"
// );

//     $url = 'https://api.worldota.net/api/b2b/v3/hotel/info/';

//     $data_hotel = array(
//         'id' => 'premier_luxury_mountain_resort',
//         'language' => 'en'
//     );

//     $json_data_hotel = json_encode($data_hotel);
    
//     $ci = curl_init();
    
//     curl_setopt($ci, CURLOPT_URL, $url);
//     curl_setopt($ci, CURLOPT_POST, 1);
//     curl_setopt($ci, CURLOPT_RETURNTRANSFER, true);
//     curl_setopt($ci, CURLOPT_POSTFIELDS, $json_data_hotel);
//     curl_setopt($ci, CURLOPT_HTTPHEADER, $headers);
//     curl_setopt($ci, CURLOPT_RETURNTRANSFER, true);
//     curl_setopt($ci, CURLOPT_SSL_VERIFYPEER, false);
    
//     $response = curl_exec($ci);

//     curl_close($ci);

//     if ($response === false)
//         return curl_error($ci);
//     else
//         echo "Response: " . print_r($response, true);

?>
