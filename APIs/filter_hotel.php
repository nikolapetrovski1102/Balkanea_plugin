<?php

// Enable error reporting for debugging purposes
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
define('WP_USE_THEMES', false);
error_reporting(E_ALL);

// Get the document root path
$path = $_SERVER['DOCUMENT_ROOT'];

// Include WordPress core file for database access and other functionalities
include_once $path . '/wp-load.php';
// require_once dirname(__DIR__, 2) . '/wp-blog-header.php';
// error_log(dirname(__DIR__, 2) . '/wp-blog-header.php');

// Start a new session or resume the existing session
session_start();

// Access the global WordPress database object
global $wpdb;

// Security check: Verify the nonce to ensure the request is valid
if (!isset($_GET['security']) || !wp_verify_nonce($_GET['security'], 'filter_hotel')) {
    http_response_code(403);
    echo json_encode(['error' => 'Invalid nonce: ' . $_GET['security']]);
    exit;
}

// Load configuration settings
$config = include '../config.php';

// Set API URL and credentials
$apiUrl = 'https://api.worldota.net/api/b2b/v3/search/serp/hotels/';
$keyId = $config['api_key'];
$apiKey = $config['api_password'];

// Initialize cURL for the API request
$ch = curl_init($apiUrl);

// Enable WordPress database error display
$wpdb->show_errors();
$prefix = $wpdb->prefix;

// Get the user's nationality using a shortcode
$nationality = do_shortcode('[userip_location type="countrycode"]') ?? "MK";

// Function to calculate a request hash for caching
function calculate_request($params) {
    return md5(json_encode($params));
}

// Function to convert currency code to symbol
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

// Function for loading hotels only from DB and not from RH
function getHotelsFromDB ($results_no, $ids_array, $ids_placeholder, $current_currency_symbol, $current_currency) {
                
            // Adding hotel to favourites
            if (is_user_logged_in()) {
                $add_to_favourites = '<div class="service-add-wishlist login" data-id="' . $hotel['id'] . '" data-type="st_hotel" title="Add to wishlist">
                    <i class="st-border-radius field-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="23" height="22" viewBox="0 0 23 22" fill="none">
                            <path fill-rule="evenodd" clip-rule="evenodd" d="M0.75 7.68998C0.75 4.18927 3.57229 1.34998 7.06 1.34998C8.79674 1.34998 10.3646 2.05596 11.5003 3.19469C12.6385 2.05561 14.2122 1.34998 15.94 1.34998C19.4277 1.34998 22.25 4.18927 22.25 7.68998C22.25 11.4395 20.5107 14.4001 18.4342 16.5276C16.3683 18.6443 13.9235 19.9861 12.3657 20.5186C12.0914 20.6147 11.7773 20.65 11.5 20.65C11.2227 20.65 10.9086 20.6147 10.6343 20.5186C9.07655 19.9861 6.63169 18.6443 4.56577 16.5276C2.48932 14.4001 0.75 11.4395 0.75 7.68998Z" fill="#232323" fill-opacity="0.3" stroke="white" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path>
                        </svg>
                    </i> 
                    <div class="lds-dual-ring"></div>
                </div>';
            } else {
                $add_to_favourites = '<a href="#" class="login" data-bs-toggle="modal" data-bs-target="#st-login-form">
                    <div class="service-add-wishlist" title="Add to wishlist">
                        <svg xmlns="http://www.w3.org/2000/svg" width="23" height="22" viewBox="0 0 23 22" fill="none">
                            <path fill-rule="evenodd" clip-rule="evenodd" d="M0.75 7.68998C0.75 4.18927 3.57229 1.34998 7.06 1.34998C8.79674 1.34998 10.3646 2.05596 11.5003 3.19469C12.6385 2.05561 14.2122 1.34998 15.94 1.34998C19.4277 1.34998 22.25 4.18927 22.25 7.68998C22.25 11.4395 20.5107 14.4001 18.4342 16.5276C16.3683 18.6443 13.9235 19.9861 12.3657 20.5186C12.0914 20.6147 11.7773 20.65 11.5 20.65C11.2227 20.65 10.9086 20.6147 10.6343 20.5186C9.07655 19.9861 6.63169 18.6443 4.56577 16.5276C2.48932 14.4001 0.75 11.4395 0.75 7.68998Z" fill="#232323" fill-opacity="0.3" stroke="white" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path>
                        </svg>
                        <div class="lds-dual-ring"></div>
                    </div>
                </a>';
            }
                
            global $wpdb;
                
            $hotel_counter = 0;
            
            foreach ($results_no as $result) {
                $hotel_id = $result->ID;
                
                $hotel = array(
                    'id' => $hotel_id,
                    'name' => get_the_title($hotel_id),
                    'link' => get_permalink($hotel_id),
                    'hotel_image' => get_the_post_thumbnail_url($hotel_id, 'full'),
                );
            
                
                $meta_fields = array(
                    'address' => 'address',
                    'price' => 'min_price',
                    'rating' => 'review_score',
                    'reviews' => 'review_count',
                    'star_rating' => 'hotel_star',
                );
            
                foreach ($meta_fields as $key => $meta_key) {
                    $value = get_post_meta($hotel_id, $meta_key, true);
                    
                    if ($key === 'price') {
                        if (empty($value)) {
                            $value = get_post_meta($hotel_id, 'price_avg', true);
                        }
                    }
                    
                    $hotel[$key] = $value;
                }
            
                $starsHTML = '';
                if (!empty($hotel['star_rating'])) {
                    $starsHTML = str_repeat('<i class="fa fa-star"></i>', (int)$hotel['star_rating']);
                }
            
                $ratingText = 'Good';
                if ($hotel['rating'] >= 4.5) {
                    $ratingText = 'Excellent';
                } elseif ($hotel['rating'] >= 4) {
                    $ratingText = 'Very Good';
                }
            
                if (!empty($hotel['price'])) {
                    if ($current_currency == "EUR"){
                        $hotel['price'] = $current_currency_symbol . ' ' . number_format((float)$hotel['price'], 2);
                    }else{
                        $hotel['price'] = $current_currency_symbol . ' ' . number_format(ceil($hotel['price'] * 61.55 ));
                    }
                } else {
                    $hotel['price'] = 'N/A';
                }
                
                $query = $wpdb->prepare("
                    SELECT c.comment_post_ID AS hotel_id, 
                           COUNT(DISTINCT c.comment_ID) AS total_reviews, 
                           AVG(CAST(cm.meta_value AS DECIMAL(10,2))) AS average_rating
                    FROM {$wpdb->prefix}comments c
                    LEFT JOIN {$wpdb->prefix}commentmeta cm 
                        ON c.comment_ID = cm.comment_id 
                        AND cm.meta_key = 'comment_rate'
                    WHERE c.comment_post_ID IN ($ids_placeholder) 
                    AND c.comment_approved = 1
                    AND cm.meta_value REGEXP '^[0-9]+(\.[0-9]+)?$'
                    GROUP BY c.comment_post_ID
                ", ...$ids_array);
                
                $review_results = $wpdb->get_results($query, ARRAY_A);
                
                if (!empty($review_results) && is_array($review_results)) {
                    foreach ($review_results as $review) {
                        $hotel_reviews[$review['hotel_id']] = [
                            'total_reviews' => $review['total_reviews'],
                            'average_rating' => round($review['average_rating'], 1)
                        ];
                    }
                } else {
                    error_log("No review results found: " . print_r($review_results, true));
                    $hotel_reviews = [];
                }
                
                $hotel['rating'] = isset($hotel_reviews[$hotel['id']]) ? $hotel_reviews[$hotel['id']]['average_rating'] : '0';
                $hotel['reviews'] = isset($hotel_reviews[$hotel['id']]) ? $hotel_reviews[$hotel['id']]['total_reviews'] . ' Reviews' : 'No Reviews';
                
                if ($hotel['rating'] >= 4.0) {
                    $ratingText = "Excellent";
                } elseif ($hotel['rating'] >= 3.0) {
                    $ratingText = "Good";
                } else if($hotel['rating'] >= 3.0 && $hotel['rating'] < 0.1) {
                    $ratingText = "Bad";
                }else{
                    $ratingText = "Not Rated";
                }

                
                $html_output .= <<<HTML
<div class="col-12 col-md-4 col-lg-4 item-service" id="available">
    <div class="services-item grid item-elementor" itemscope itemtype="https://schema.org/Hotel" data-id="{$hotel['id']}">
        <div class="item service-border st-border-radius">
            <div class="featured-image">
                <a href="{$hotel['link']}">
                    <img src="{$hotel['hotel_image']}" alt="{$hotel['name']}" class="img-responsive">
                </a>
            </div>
            <div class="content-item">
                <div class="content-inner has-matchHeight">
                    <div class="st-stars">
                        {$starsHTML}
                    </div>
                    <h3 class="title" itemprop="name">
                        <a href="{$hotel['link']}" class="c-main">{$hotel['name']}</a>
                    </h3>
                    <div class="sub-title d-flex align-items-center" itemprop="address">
                        <span>{$hotel['address']}</span>
                    </div>
                </div>
                <div class="section-footer">
                <div class="reviews">
                    <span class="rate mr-0">{$hotel['rating']}/5</span>
                    <strong class="rating-text">{$ratingText}</strong>
                    <span class="summary">({$hotel['reviews']})</span>
                </div>

                    <div class="price-wrapper d-flex align-items-center">
                        From: <span class="price">{$hotel['price']}</span>
                        <span class="unit">/night</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
HTML;

$hotel_counter++;

}
            
$response_hotel_array = [
    "status" => "ok",
    "html" => $html_output,
    "hotel_count" => $hotel_counter,
    "load_more" => $load_more
];

return $response_hotel_array;

}

// Check if required GET parameters are set for filtering by taxonomy
if (isset($_GET['data_ids']) && isset($_GET['taxonomy'])){
    
    try{
        $data_ids = $_GET['data_ids'];
        $term_ids = $_GET['taxonomy'];
        
        // Prepare term ID list for SQL query
        if (str_contains(',', $term_ids)){
            $term_id_list = implode(',', $term_ids);
            $term_id_count = count($term_ids);
        }
        else{
            $term_id_list = $term_ids;
            $term_id_count = 1;
        }
        
        $all_results = [];
    
        // Query the database for matching hotel IDs
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
        
        // Return the results as JSON
        echo json_encode($all_results, true);
    }catch (\Exception $ex){
        error_log($ex->getMessage());
        exit();
    }

}
    
// Check if required GET parameters are set for filtering by location and date
else if ((isset($_GET['ids']) ||  isset($_GET['location_id'])) && isset($_GET['start']) && isset($_GET['end']) && isset($_GET['currency']) ) {

    try{
    
        // Prepare request parameters
        $params = [
            'location_id' => $_GET['location_id'] ?? "",
            'start' => $_GET['start'],
            'end' => $_GET['end'],
            'currency' => $_GET['currency'],
            'adults' => $_GET['adults'] ?? 1,
            'children' => $_GET['children'] ?? 0,
            'page' => isset($_GET['page']) ? $_GET['page'] : 1
        ];
        
        $offset_page = isset($_GET['page']) ? $_GET['page'] : 0;
        $offset = $offset_page == 1 ? 0 : ($offset_page - 1) * 100;
    
        // Calculate request hash for caching
        $request_hash = calculate_request($params);
    
        // Check if a similar request is already running
        if (isset($_SESSION['active_request']) && $_SESSION['active_request']['hash'] === $request_hash) {
            while ($_SESSION['active_request']['status'] === 'running') {
                usleep(10000);
            }
    
            // Return cached response if available
            if (isset($_SESSION['cached_response'])) {
                echo $_SESSION['cached_response'];
                exit();
            }
        }
    
        // Mark the request as active
        $_SESSION['active_request'] = [
            'hash' => $request_hash,
            'status' => 'running'
        ];
    
        $location_id = $_GET['location_id'] ?? "";
        if (isset($_GET['location_id'])) {
            
            // Query the database for post IDs based on location
            // Offset must be in limit of 100 hotels because RateHawk handles max of 100 hotels
            $query = $wpdb->prepare(
                "SELECT DISTINCT(post_id) FROM Y7FXuNUTt_st_location_relationships WHERE location_from = %d ORDER BY post_id LIMIT 100 OFFSET %d;",
                $location_id,
                $offset
            );
            
            $results = $wpdb->get_results($query);
        
            $load_more = (count($results) < 100) ? false : true;
        
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
        
            // Query the database for post details
            // post_author must be 6961, this indicates that all gathered hotels are from ratehawk
            $query = $wpdb->prepare(
                "SELECT ID, post_name FROM {$wpdb->prefix}posts WHERE ID IN ($ids_placeholder) AND post_author = 6961",
                ...$ids_array
            );
        } else if (!empty($location_id)) {
            $ids_array = $post_ids;
        
            $ids_placeholder = implode(',', array_fill(0, count($ids_array), '%d'));
        
            // Query the database for post details
            // post_author must be 6961, this indicates that all gathered hotels are from ratehawk
            $query = $wpdb->prepare(
                "SELECT ID, post_name FROM {$wpdb->prefix}posts WHERE ID IN ($ids_placeholder) AND post_author = 6961",
                ...$ids_array
            );
        }else{
            error_log("No loaction or ids found, ids value: " . print_r($ids, true) . " location value: " . print_r($location_id));
            return;
            exit;
        }
    
        $results = $wpdb->get_results($query);
        
        // Set dynamic timeout value based on the number of results
        $dynamic_timout_value = count($results) > 30 ? 30 : ceil(count($results) / 2);
        
        if (empty($results)){
            
            $query_no = $wpdb->prepare(
                "SELECT ID, post_name FROM {$wpdb->prefix}posts WHERE ID IN ($ids_placeholder) AND post_author != 6961 AND post_type = 'st_hotel'",
                ...$ids_array
            );
        
            $results_no = $wpdb->get_results($query_no);
            
            if (!empty($results_no)){
                $response_hotel_array = getHotelsFromDB($results_no, $ids_array, $ids_placeholder, $current_currency_symbol, $current_currency);
            
                $_SESSION['cached_response'] = json_encode($response_hotel_array);
                echo json_encode($response_hotel_array);
                $_SESSION['active_request']['status'] = 'completed';
                exit();
            }else{
                $_SESSION['cached_response'] = json_encode($response_hotel_array);
                echo json_encode($response_hotel_array);
                $_SESSION['active_request']['status'] = 'completed';
                exit();
            }
        }
    
        if (!empty($results)) {
            
            $all_ids = array_column($results, 'ID');
            
            $post_names = array_map(function($result) {
                return $result->post_name;
            }, $results);
    
            // Prepare the request body for the API call
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
    
            // Set cURL options for the API request
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Authorization: Basic ' . base64_encode("$keyId:$apiKey")
            ]);
            
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            
            // Execute the API request and close the cURL session
            $response = curl_exec($ch);
    
            curl_close($ch);
    
            $post_names = array_column($results, 'post_name');
            
            $responseData = json_decode($response, true);
            
            // Check for errors in the response
            if (!isset($responseData) || $responseData['status'] == 'error'){
                error_log("Error during response");
                error_log(print_r($responseData, true));
            }
            
            // Handle validation errors in the response
            if (isset($responseData['debug']['validation_error']) && $responseData['debug']['validation_error'] === "checkout cannot be later than 30 days since checkin") {
                error_log("Validation Error: " . $responseData['debug']['validation_error']);
                $_SESSION['cached_response'] = json_encode(["error" => "The checkout date cannot exceed 30 days from the check-in date. Please select a checkout date within this timeframe."]);
                $_SESSION['active_request']['status'] = 'completed';
                echo json_encode(["error" => "The checkout date cannot exceed 30 days from the check-in date. Please select a checkout date within this timeframe."]);
                exit;
            }
            
            // Filter and map found hotel IDs
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
                            
                            // Correcting the hotel_image URL using double quotes for variable interpolation
                            $found_ids[$result->ID] = [
                                "price" => $current_currency_symbol . ' ' . $price,
                                "hotel_id" => $hotel['id'],
                                "hotel_image" => "https://staging.balkanea.com/wp-content/uploads/RateHawk/{$hotel['id']}/{$hotel['id']}-1.jpg",
                            ];
                            break;
                        }
                    }
                }
            }

            // Prepare the response with found IDs
            $response_call = json_encode([
                'found_ids' => json_encode($found_ids, true),
                'all_ids' => $all_ids
            ]);
        
            $found_ids_keys = array_keys($found_ids); // Extracting only the keys (IDs)
            $found_ids_placeholders = implode(',', array_fill(0, count($found_ids_keys), '%d')); 
            
            if (!$found_ids_placeholders){
                $response_hotel_array = [
                    "status" => "ok",
                    "html" => $html_output,
                    "hotel_count" => $hotel_count,
                    "load_more" => $load_more
                ];
                
                $_SESSION['cached_response'] = json_encode($response_hotel_array);
                echo json_encode($response_hotel_array);
                
                exit();
            }
            $query = $wpdb->prepare("
                SELECT id, post_title AS name, meta_value AS image
                FROM {$wpdb->prefix}posts 
                LEFT JOIN {$wpdb->prefix}postmeta ON ({$wpdb->prefix}posts.ID = {$wpdb->prefix}postmeta.post_id AND {$wpdb->prefix}postmeta.meta_key = '_thumbnail_id')
                WHERE ID IN ($found_ids_placeholders) 
                AND post_type = 'st_hotel'
                AND post_status = 'publish'
            ", $found_ids_keys);
            
            $hotels = $wpdb->get_results($query, ARRAY_A);
            
            if (empty($found_ids_keys)) {
                error_log("No hotel IDs found for review query.");
                $hotel_reviews = [];
            } else {
                $placeholders = implode(',', array_fill(0, count($found_ids_keys), '%d'));
            
            $query = $wpdb->prepare("
                SELECT c.comment_post_ID AS hotel_id, 
                       COUNT(DISTINCT c.comment_ID) AS total_reviews, 
                       AVG(CAST(cm.meta_value AS DECIMAL(10,2))) AS average_rating
                FROM {$wpdb->prefix}comments c
                LEFT JOIN {$wpdb->prefix}commentmeta cm 
                    ON c.comment_ID = cm.comment_id 
                    AND cm.meta_key = 'comment_rate'
                WHERE c.comment_post_ID IN ($found_ids_placeholders) 
                AND c.comment_approved = 1
                AND cm.meta_value REGEXP '^[0-9]+(\.[0-9]+)?$'
                GROUP BY c.comment_post_ID
            ", $found_ids_keys);
            
                $review_results = $wpdb->get_results($query, ARRAY_A);
            }


            if (!empty($review_results) && is_array($review_results)) {
                foreach ($review_results as $review) {
                    $hotel_reviews[$review['hotel_id']] = [
                        'total_reviews' => $review['total_reviews'],
                        'average_rating' => round($review['average_rating'], 1)
                    ];
                }
            } else {
                error_log("No review results found: " . print_r($review_results, true));
                $hotel_reviews = []; // Ensure it's an array to avoid errors
            }

$html_output = '';
$hotel_count = 0;

$query_no = $wpdb->prepare(
    "SELECT ID, post_name FROM {$wpdb->prefix}posts WHERE ID IN ($ids_placeholder) AND post_author != 6961 AND post_type = 'st_hotel'",
    ...$ids_array
);

$results_no = $wpdb->get_results($query_no);

if (!empty($results_no)){
    $db_hotels = getHotelsFromDB($results_no, $ids_array, $ids_placeholder, $current_currency_symbol, $current_currency);
}

foreach ($hotels as $hotel) {
    $startDate = DateTime::createFromFormat('Y-m-d', $params['start']);
    $endDate = DateTime::createFromFormat('Y-m-d', $params['end']);

    $query_args = [
        'start' => $startDate->format('d/m/Y'),
        'end' => $endDate->format('d/m/Y'),
        'date' => urlencode($startDate->format('d/m/Y') . ' 12:00 am-' . $endDate->format('d/m/Y') . ' 11:59 pm'),
        'adult_number' => $params['adults'],
        'room_num_search' => 1, // Fixed value
        'search' => 'yes'
    ];
    
    $hotel['link'] = add_query_arg($query_args, get_permalink($hotel['id']));
    $hotel['address'] = get_post_meta($hotel['id'], 'address', true);
    $starCount = get_post_meta($hotel['id'], 'hotel_star', true);
    $starCount = is_numeric($starCount) ? (int) $starCount : 0; // Ensure it's a valid integer
    
    $starsHTML = ''; // Initialize stars output
    
    for($i = 0; $i < $starCount; $i++){
        $starsHTML .= '<span class="stt-icon stt-icon-star1' . $starClass . '"></span>';
    }

    $hotel['rating'] = isset($hotel_reviews[$hotel['id']]) ? $hotel_reviews[$hotel['id']]['average_rating'] : '0';
    $hotel['reviews'] = isset($hotel_reviews[$hotel['id']]) ? $hotel_reviews[$hotel['id']]['total_reviews'] . ' Reviews' : 'No Reviews';

    if ($hotel['rating'] >= 4.0) {
        $ratingText = "Excellent";
    } elseif ($hotel['rating'] >= 3.0) {
        $ratingText = "Good";
    } else if($hotel['rating'] >= 3.0 && $hotel['rating'] < 0.1) {
        $ratingText = "Bad";
    }else{
        $ratingText = "Not Rated";
    }


    if (isset($found_ids[$hotel['id']])) {
        $hotel['price'] = $found_ids[$hotel['id']]['price'];
        // BAL-673 - START
        if (post_type_supports(get_post_type($hotel['id']), 'thumbnail')) {
            $hotel['hotel_image'] = get_the_post_thumbnail_url($hotel['id'], 'full');
        }
        // BAL-673 - END
    }else{
        continue;
    }
    
    // Adding hotel to favourites
    if (is_user_logged_in()) {
        $add_to_favourites = '<div class="service-add-wishlist login" data-id="' . $hotel['id'] . '" data-type="st_hotel" title="Add to wishlist">
            <i class="st-border-radius field-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="23" height="22" viewBox="0 0 23 22" fill="none">
                    <path fill-rule="evenodd" clip-rule="evenodd" d="M0.75 7.68998C0.75 4.18927 3.57229 1.34998 7.06 1.34998C8.79674 1.34998 10.3646 2.05596 11.5003 3.19469C12.6385 2.05561 14.2122 1.34998 15.94 1.34998C19.4277 1.34998 22.25 4.18927 22.25 7.68998C22.25 11.4395 20.5107 14.4001 18.4342 16.5276C16.3683 18.6443 13.9235 19.9861 12.3657 20.5186C12.0914 20.6147 11.7773 20.65 11.5 20.65C11.2227 20.65 10.9086 20.6147 10.6343 20.5186C9.07655 19.9861 6.63169 18.6443 4.56577 16.5276C2.48932 14.4001 0.75 11.4395 0.75 7.68998Z" fill="#232323" fill-opacity="0.3" stroke="white" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path>
                </svg>
            </i> 
            <div class="lds-dual-ring"></div>
        </div>';
    } else {
        $add_to_favourites = '<a href="#" class="login" data-bs-toggle="modal" data-bs-target="#st-login-form">
            <div class="service-add-wishlist" title="Add to wishlist">
                <svg xmlns="http://www.w3.org/2000/svg" width="23" height="22" viewBox="0 0 23 22" fill="none">
                    <path fill-rule="evenodd" clip-rule="evenodd" d="M0.75 7.68998C0.75 4.18927 3.57229 1.34998 7.06 1.34998C8.79674 1.34998 10.3646 2.05596 11.5003 3.19469C12.6385 2.05561 14.2122 1.34998 15.94 1.34998C19.4277 1.34998 22.25 4.18927 22.25 7.68998C22.25 11.4395 20.5107 14.4001 18.4342 16.5276C16.3683 18.6443 13.9235 19.9861 12.3657 20.5186C12.0914 20.6147 11.7773 20.65 11.5 20.65C11.2227 20.65 10.9086 20.6147 10.6343 20.5186C9.07655 19.9861 6.63169 18.6443 4.56577 16.5276C2.48932 14.4001 0.75 11.4395 0.75 7.68998Z" fill="#232323" fill-opacity="0.3" stroke="white" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path>
                </svg>
                <div class="lds-dual-ring"></div>
            </div>
        </a>';
    }


    // Build the HTML output
    $html_output .= <<<HTML
<div class="col-12 col-md-4 col-lg-4 item-service" id="available">
    <div class="services-item grid item-elementor" itemscope itemtype="https://schema.org/Hotel" data-id="{$hotel['id']}">
        <div class="item service-border st-border-radius">
            <div class="featured-image">
                <a href="{$hotel['link']}">
                    {$add_to_favourites}
    				<img src="{$hotel['hotel_image']}" alt="{$hotel['name']}" class="img-responsive">
                </a>
            </div>
            <div class="content-item">
                <div class="content-inner has-matchHeight">
                    <div class="st-stars">
                        {$starsHTML}
                    </div>
                    <h3 class="title" itemprop="name">
                        <a href="{$hotel['link']}" class="c-main">{$hotel['name']}</a>
                    </h3>
                    <div class="sub-title d-flex align-items-center" itemprop="address">
                        <span>{$hotel['address']}</span>
                    </div>
                </div>
                <div class="section-footer">
                <div class="reviews">
                    <span class="rate mr-0">{$hotel['rating']}/5</span>
                    <strong class="rating-text">{$ratingText}</strong>
                    <span class="summary">({$hotel['reviews']})</span>
                </div>

                    <div class="price-wrapper d-flex align-items-center">
                        From: <span class="price">{$hotel['price']}</span>
                        <span class="unit">/night</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
HTML;

$hotel_count++;

}
        // combined_response for showing hotels from RH and from DB
        $combined_response = [
            "status" => "ok",
            "html" => $db_hotels['html'] . $html_output,
            "hotel_count" => $db_hotels['hotel_count'] + $hotel_count,
            "load_more" => $db_hotels['load_more'] || $load_more // Assuming boolean values
        ];
    
            if ($response === false){
                error_log(curl_error($ch));
                echo 'Curl error: ' . curl_error($ch);
            }
            else
                $_SESSION['cached_response'] = json_encode($combined_response);
                echo json_encode($combined_response);
                
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