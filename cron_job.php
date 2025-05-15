<?php
define('SHORTINIT', true);
    define('WP_CLI', true);
    $_SERVER['REMOTE_ADDR'] = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    define('WP_USE_THEMES', false); // or true, depending on context
    define('DOING_CRON', true); // Helps WP know it's a cron job

    $config = include __DIR__ . '/config.php';
    $keyId = $config['api_key'];
    $apiKey = $config['api_password'];

// if (!isset($_SERVER['PHP_AUTH_USER']) || !isset($_SERVER['PHP_AUTH_PW'])){
//     logToRegionFile("Authentication headers missing", "global", "ERROR");
//     header('WWW-Authenticate: Basic realm="Restricted Area"');
//     header('HTTP/1.0 401 Unauthorized');
//     echo 'Authorization header missing';
//     exit;
// }
// if ($_SERVER['PHP_AUTH_USER'] !== $keyId || $_SERVER['PHP_AUTH_PW'] !== $apiKey) {
//     logToRegionFile("Invalid credentials provided: " . $_SERVER['PHP_AUTH_USER'], "global", "ERROR");
//     header('WWW-Authenticate: Basic realm="Restricted Area"');
//     header('HTTP/1.0 401 Unauthorized');
//     echo 'Unauthorized Access';
//     exit;
// }

logToRegionFile("Authentication successful");

use Models\Amenity;
use Models\HotelRoom;
use Models\ImageInserter;
use Models\PostMetaValues;
use Models\PostsHotel;
use Models\PostsRoom;
use Models\ProcessAmenity;
use Models\St_Hotel;
use Models\LocationNested;
use Models\LocationRelationship;
use Models\CurrencyModel;
use data\HotelFlag;

    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);

    $path = realpath(__DIR__ . '/../');

    require_once __DIR__ .'/Models/Amenity.php';
    require_once __DIR__ .'/Models/ProcessAmenity.php';
    require_once __DIR__ .'/Models/PostsHotel.php';
    require_once __DIR__ .'/Models/PostsRoom.php';
    require_once __DIR__ .'/Models/HotelRoom.php';
    require_once __DIR__ .'/Models/PostMetaValues.php';
    require_once __DIR__ .'/Models/St_Hotel.php';
    require_once __DIR__ .'/Models/ImageInsert.php';
    require_once __DIR__ .'/Models/RoomAvailability.php';
    require_once __DIR__ .'/Models/LocationRelationship.php';
    require_once __DIR__ .'/Models/LocationNested.php';
    require_once __DIR__ .'/Models/CurrencyModel.php';
    require_once __DIR__ .'/data/data.php';
    require_once __DIR__ .'/data/track_data.php';
    require_once __DIR__ .'/data/HotelFlag.php';
    require_once __DIR__ .'/HttpRequests.php';

    require_once $path . '/wp-load.php';

    global $wpdb;

    $wpdb->show_errors();
    $prefix = $wpdb->prefix;

    $credentials = base64_encode($keyId . ':' . $apiKey);

    $headers = array(
        'Authorization: Basic ' . $credentials,
        'Content-Type: application/json'
    );

    function clearWpDb() {
    }
    
    function logToRegionFile($message, $currentRegion = 'global', $logLevel = 'INFO') {
        error_log("Logging to $currentRegion");
        $logDate = date('Y-m-d H:i:s');
        $logMessage = "[$logDate] [$logLevel] $message" . PHP_EOL;
        
        $logFilePath = __DIR__ . "/logs/{$currentRegion}_logs.log";
        
        if (!is_dir(__DIR__ . '/logs')) {
            mkdir(__DIR__ . '/logs', 0755, true);
        }
        
        file_put_contents($logFilePath, $logMessage, FILE_APPEND);
    }


    try{
        $regionTotal = 0;
        $allHotels = trackNextRegion();

        $regionData = json_decode($allHotels, true);

        if ($regionData === null) {
            logToRegionFile("JSON decode failed: " . json_last_error_msg() . " - Raw data: " . substr($allHotels, 0, 1000), "global", "ERROR");
            throw new Exception("Invalid JSON: " . json_last_error_msg());
        }

        $country = $regionData['country'];
        $regions = $regionData['regions'];

        foreach ($regions as $region) {
            
            logToRegionFile("processing region $region");
            
            $hotelEntries = getHotelDetails($region, $headers);
        //   $hotelEntries = getHotelDetailsLocal("438"); //get data from test_data_hotels2.json

            logToRegionFile("Hotel entries gathered");

            if (!is_array($hotelEntries) || empty($hotelEntries)) continue;

            logToRegionFile("Hotel entries not null");

            foreach ($hotelEntries as $hotel_response) {
              try {
                  
              logToRegionFile("Processing hotel");
                  
                $singleHotelStart = microtime(true);

                $hotel = json_decode($hotel_response, true);
                
                
                if (!$hotel) {
                    logToRegionFile("Failed to decode hotel JSON: " . json_last_error_msg(), "global", "ERROR");
                    continue;
                }

                $posts_hotel = new PostsHotel($wpdb);

                $hotel_name = $hotel["id"];
                $current_country_code = $hotel['region']['country_code'];
                logToRegionFile("_____________________________________________________________", $current_country_code, "INFO");
                logToRegionFile("Processing region: " . $region . " with " . count($hotelEntries) . " hotels", $current_country_code, "INFO");
                
                logToRegionFile("Processing hotel: " . $hotel_name, $current_country_code, "INFO");

                $posts_hotel->post_name = $hotel_name;

                // Clear any pending result sets before checking if hotel exists
                clearWpDb();

                $post_content = '';
                $post_excerpt = '';
                $post_title = $hotel['name'];
                $address = $hotel['address'];
                $star_rating = $hotel['star_rating'];
                $latitude = $hotel['latitude'];
                $longitude = $hotel['longitude'];
                $post_id_name = $hotel['id'];
                $img_urls = '';
                $hotel_location = $hotel['region'];
                $hotel_country_code = $hotel['region'];
                $metapolicy_struct = json_encode($hotel['metapolicy_struct']);

                echo "\n{$hotel['id']}";

                $location_nested = new LocationNested($wpdb);
                logToRegionFile("Country code: " . $current_country_code, $currentRegion, "INFO");
                $location_nested->location_country = $hotel_country_code['country_code'];

                // Execute query and store result before proceeding
                $parent_location_exists_json = $location_nested->parentLocationExists();
                // Make sure to consume the result
                clearWpDb();

                $parent_location_exists = json_decode($parent_location_exists_json);
                $parent_location = $parent_location_exists->ID;
                $parent_location_id = $parent_location_exists->location_id;

                echo "\n$parent_location";

                if ($parent_location){
                    echo "\nLocation found in DB";
                    $location_nested->parent_id = $parent_location;
                } else {
                    $location_nested->parent_id = $parent_location;
                    logToRegionFile("No parent location found", $current_country_code, "INFO");
                }

                $location_nested->location_id = $hotel_location['id'];
                $location_nested->name = $hotel_location['name'];
                $location_nested->language = 'en';
                $location_nested->status = 'publish';

                // Create location and ensure the result is consumed
                $location_result = $location_nested->create();
                clearWpDb();

                foreach ($hotel['description_struct'] as $content){
                    if ($content['title'] == 'Location'){
                        foreach ($content['paragraphs'] as $paragraph){
                            $post_excerpt .= $paragraph . "\n\n";
                        }
                    }
                    else{
                        foreach ($content['paragraphs'] as $paragraph){
                            $post_content .= $paragraph . "\n\n";
                        }
                    }
                }

                $posts_hotel->post_content = $post_content;
                $posts_hotel->post_title = $hotel['name'];
                $posts_hotel->post_excerpt = $post_excerpt;
                $posts_hotel->post_status = 'publish';
                $posts_hotel->post_password = '';
                $posts_hotel->post_name = $post_id_name;
                $posts_hotel->to_ping = '';
                $posts_hotel->pinged = '';
                $posts_hotel->post_content_filtered = '';
                $posts_hotel->guid = '';
                $posts_hotel->post_mime_type = '';

                // Get post and ensure result is consumed
                $post_id = $posts_hotel->get();
                clearWpDb();

                $amenities_model = new ProcessAmenity($wpdb);
                $location_relationships = new LocationRelationship($wpdb);

                if ($post_id){
                    echo "\nHotel found in DB";

                    $posts_hotel->id = $post_id;
                    $updated_id = $posts_hotel->update();
                    clearWpDb();

                    if ($hotel['amenity_groups'] != null && count($hotel['amenity_groups']) > 0)
                    {
                        logToRegionFile("Processing amenities for hotel\n", $current_country_code, "INFO");
                        $amenities_model->amenities = $hotel['amenity_groups'][0];
                        $amenities_model->post_id = $updated_id;
                        $amenities = $amenities_model->getAmenities();
                        clearWpDb();
                    } else {
                        logToRegionFile("No amenities found for hotel", $current_country_code, "INFO");
                    }

                    logToRegionFile("Finished processing amenities", $current_country_code, "INFO");

                    $location_relationships->post_id = $post_id;
                    $location_relationships->location_from = array($parent_location_id, $hotel_location['id']);
                    $location_relationships->location_to = 0;
                    $location_relationships->post_type = 'st_hotel';
                    $location_relationships->location_type = 'multi_location';

                    $location_result = $location_relationships->insertLocationRelationship();
                    clearWpDb();
                }
                else{
                    echo "\nHotel not found in DB";

                    $post_id = $posts_hotel->create();
                    clearWpDb();

                    if (is_array($hotel['amenity_groups']) && count($hotel['amenity_groups']) > 0) {
                        $amenities_model->amenities = $hotel['amenity_groups'][0];
                    } else {
                        $amenities_model->amenities = array();
                    }

                    $amenities_model->post_id = $post_id;

                    $amenities = $amenities_model->getAmenities();
                    clearWpDb();

                    $location_relationships->post_id = $post_id;
                    $location_relationships->location_from = array($parent_location_id, $hotel_location['id']);
                    $location_relationships->location_to = 0;
                    $location_relationships->post_type = 'st_hotel';
                    $location_relationships->location_type = 'multi_location';

                    $location_result = $location_relationships->insertLocationRelationship();
                    clearWpDb();
                }

                $prices = array();

                $price_avg = 0;
                $price_min = 0;
                $room_id;

                $posts_room = new PostsRoom($wpdb);
                $post_meta = new PostMetaValues($wpdb);
                $post_images = new ImageInserter($wpdb);

                try {
                    $counter = 0;

                    foreach ($hotel['room_groups'] as $room) {
                        // Clear any existing result sets
                clearWpDb();

                        $posts_room->post_title = $room['name'];
                        $posts_room->post_content = $post_content;
                        $posts_room->post_excerpt = $post_excerpt;
                        $posts_room->post_status = 'publish';
                        $posts_room->post_password = '';
                        $posts_room->post_name = $post_id_name . '-' . str_replace(' ', '-', $room['name']) . '-' . $room['room_group_id'];
                        $posts_room->to_ping = '';
                        $posts_room->pinged = '';
                        $posts_room->post_content_filtered = '';
                        $posts_room->post_parent = $post_id;
                        $posts_room->guid = '';
                        $posts_room->post_mime_type = '';

                        $posts_room_exsists = $posts_room->get();
                clearWpDb();

                        $post_images->hotel = $room;
                        $post_images->directory_url = ''; //$hotel['id'] . '/' . str_replace(' ', '-', $room['name_struct']['main_name']);
                        $post_images->post_title = $hotel['name'] . ' - ' . $room['name'];
                        $post_images->post_id_name = $post_id_name;
                        $post_images->provider = 'RateHawk';
                        $post_images->default_image = $hotel['images'];

                        $post_meta->meta_values = array(
                            'rate_review' => 0,
                            'min_price' => $price_min,
                            'meta_value' => 'classic-editor',
                            '_edit_lock' => '1720094804:14',
                            '_edit_last' => 14,
                            'discount_type' => 'percent',
                            'room_parent' => $post_id,
                            'number_room' => $room['room_group_id'],
                            'st_booking_option_type' => 'instant',
                            'st_custom_layout' => 3,
                            'disable_adult_name' => 'off',
                            'disable_children_name' => 'on',
                            'price_by_per_person' => 'off',
                            'allow_full_day' => 'on',
                            'price' => 0,
                            'discount_type_no_day' => 'percent',
                            'extra_price_unit' => 'perday',
                            'adult_number' => 9,
                            'children_number' => 9,
                            'st_room_external_booking' => 'off',
                            'default_state' => 'available',
                            'st_allow_cancel' => 'off',
                            'st_cancel_percent' => 0,
                            'is_meta_payment_gateway_st_submit_form' => 'on',
                            'is_meta_payment_gateway_vina_stripe' => 'on',
                            'multi_location' => '_'. $hotel_location['id'] . '_,_' . $parent_location_id . '_',
                            '_yoast_wpseo_primary_room_type' => 66,
                            '_yoast_wpseo_primary_room-facilities' => 43,
                            '_yoast_wpseo_focuskw' => $room['name_struct']['main_name'],
                            '_yoast_wpseo_metadesc' => $room['name_struct']['main_name'] . ' in ' . $hotel['name'],
                            '_yoast_wpseo_linkdex' => 40,
                            '_yoast_wpseo_content_score' => 90,
                            '_yoast_wpseo_estimated-reading-time-minutes' => NULL,
                            'bed_number' =>  $room['rg_ext']['capacity'] == 0 ? 1 : $room['rg_ext']['capacity'],
                            'id_location' => '',
                            'location_id' => '',
                            '_thumbnail_id' => 0,
                            'gallery' => '',
                            'address' => $address,
                            '_wp_old_slug' => $hotel['id'] . '-' . str_replace(' ', '_', $room['name_struct']['main_name']),
                        );

                        if ($posts_room_exsists){
                            $post_room_id = $posts_room_exsists->ID;
                            $posts_room->id = $post_room_id;
                            $post_meta->post_id = $post_room_id;
                            $post_images->post_id = $post_room_id;

                            $post_image_array_ids = $post_images->insertImages();
                            clearWpDb();

                            $post_meta->meta_values['_thumbnail_id'] = $post_image_array_ids != null ? explode(",", $post_image_array_ids)[0] : '';
                            $post_meta->meta_values['gallery'] = $post_image_array_ids ?? '';

                            $update_result = $posts_room->update();
                            clearWpDb();

                            $meta_update_result = $post_meta->update();
                            clearWpDb();

                        }
                        else{
                            $post_room_id = $posts_room->create();
                            clearWpDb();

                            $post_meta->post_id = $post_room_id;
                            $post_images->post_id = $post_room_id;

                            $post_image_array_ids = $post_images->insertImages();
                            clearWpDb();

                            $post_meta->meta_values['_thumbnail_id'] = $post_image_array_ids != null ? explode(",", $post_image_array_ids)[0] : '';
                            $post_meta->meta_values['gallery'] = $post_image_array_ids ?? '';

                            $meta_create_result = $post_meta->create();
                            clearWpDb();
                        }

                        $amenities_model->amenities = $room['room_amenities'];
                        $amenities_model->post_id = $post_room_id;

                        $amenities = $amenities_model->getRoomAmenities();
                        clearWpDb();

                        $hotel_room_model = new HotelRoom($wpdb);

                        $hotel_room_model->post_id = $post_room_id;
                        $hotel_room_model->room_parent = $post_id;
                        $hotel_room_model->multi_location = '_'. $hotel_location['id'] . '_,_' . $parent_location_id . '_';
                        $hotel_room_model->id_location = '';
                        $hotel_room_model->address = $address;
                        $hotel_room_model->allow_full_day = 'on';
                        $hotel_room_model->price = 0;
                        $hotel_room_model->number_room = $room['rg_ext']['capacity'] == 0 ? 1 : $room['rg_ext']['capacity'];
                        $hotel_room_model->discount_rate = '';
                        $hotel_room_model->adult_number = 2;
                        $hotel_room_model->child_number = 0;
                        $hotel_room_model->status = 'draft';
                        $hotel_room_model->adult_price = 0;
                        $hotel_room_model->child_price = 0;

                        $room_id = $post_room_id;

                        $hotel_room = $hotel_room_model->get();
                        clearWpDb();

                        $hotel_room ?? logToRegionFile("Hotel room does not exist", $current_country_code, "INFO");

                        if ($hotel_room) {
                            $hotel_room_update = $hotel_room_model->update();
                            clearWpDb();
                        } else {
                            $hotel_room_create = $hotel_room_model->create();
                            clearWpDb();
                        }
                    }

                    if ($wpdb->last_error) {
                        logToRegionFile("wpdb last error after processing rooms: " . $wpdb->last_error, $current_country_code, "ERROR");
                        echo 'wpdb last error: ' . $wpdb->last_error . '<br>';
                    } else {
                        logToRegionFile("All rooms processed successfully", $current_country_code, "INFO");
                        echo '<br>Data for posts hotel room inserted successfully<br>';
                    }

                } catch (Exception $e) {
                    logToRegionFile("Caught exception in rooms processing: " . $e->getMessage() . " - " . $e->getTraceAsString(), $current_country_code, "ERROR");
                    echo 'Caught exception: ',  $e->getMessage(), "\n";
                }

                //ST_Hotel
                $st_hotel = new St_Hotel($wpdb);

                $st_hotel->post_id = (int) $post_id;
                $st_hotel->address = $address;
                $st_hotel->rate_review = 0;
                $st_hotel->hotel_star = $star_rating;
                $st_hotel->price_avg = $price_avg;
                $st_hotel->min_price = $price_min;
                $st_hotel->map_lat = $latitude;
                $st_hotel->map_lng = $longitude;

                if ($st_hotel->get()) {
                    $st_hotel_update = $st_hotel->update();
                    clearWpDb();
                } else {
                    $st_hotel_create = $st_hotel->create();
                    clearWpDb();
                }

                $post_image_array_ids = '';

                $post_images->hotel = $hotel;
                $post_images->directory_url = $hotel['id'];
                $post_images->post_title = $post_title;
                $post_images->post_id_name = $post_id_name;
                $post_images->post_id = $post_id;
                $post_images->provider = 'RateHawk';

                $post_meta = new PostMetaValues($wpdb);
                $post_meta->post_id = $post_id;

                $post_image_array_ids = $post_images->insertImages();
                clearWpDb();

                if ($post_image_array_ids == ''){
                    $meta_result = $post_meta->read('gallery');
                    clearWpDb();
                    $post_image_array_ids = $meta_result->meta_value;
                }

                $post_meta->meta_values = array(
                    'rate_review' => 0,
                    'price_avg' => $price_avg,
                    'min_price' => $price_min,
                    'meta_value' => 'classic-editor',
                    '_edit_lock' => '1720094804:14',
                    '_edit_last' => 14,
                    '_tve_js_modules_gutenberg' => 'a:0:{}',
                    'st_google_map' => 'a:4:{s:3:"lat";s:' . strlen($latitude) . ':"' . $latitude . '";s:3:"lng";s:' . strlen($longitude) . ':"' . $longitude . '";s:4:"zoom";s:2:"13";s:4:"type";s:0:"";}',
                    'multi_location' => '_' . $hotel_location['id'] . '_,_' . $parent_location_id . '_',
                    'address' => $address,
                    'is_featured' => 'off',
                    'st_hotel_external_booking' => 'off',
                    'hotel_star' => $star_rating,
                    'is_auto_caculate' => 'on',
                    'allow_full_day' => 'on',
                    'check_in_time' => substr($hotel['check_in_time'], 0, -3),
                    'check_out_time' => substr($hotel['check_out_time'], 0, -3),
                    'hotel_booking_period' => 0,
                    'min_book_room' => 0,
                    'id_location' => '',
                    'location_id' => '',
                    'map_lat' => $latitude,
                    'map_lng' => $longitude,
                    'map_zoom' => 13,
                    'map_type' => '',
                    '_yoast_wpseo_primary_hotel-theme' => 27,
                    '_yoast_wpseo_primary_hotel-facilities' => 425,
                    '_yoast_wpseo_focuskw' => $post_title,
                    '_yoast_wpseo_metadesc' => $post_excerpt,
                    '_yoast_wpseo_linkdex' => 71,
                    '_yoast_wpseo_content_score' => 60,
                    '_yoast_wpseo_estimated-reading-time-minutes' => NULL,
                    'hotel_layout_style' => 5,
                    'hotel_policy' => 'a:1:{i:0;a:2:{s:5:"title";s:0:"";s:18:"policy_description";s:' . strlen($hotel['metapolicy_extra_info'] ?? '') . ':"' . $hotel['metapolicy_extra_info'] ?? '' . '";}}',
                    '_thumbnail_id' => $post_image_array_ids != null ? explode(",", $post_image_array_ids)[0] : '',
                    'gallery' => $post_image_array_ids ?? '',
                    '_wp_old_date' => date('YYYY-mm-dd'),
                    'provider' => 'RateHawk',
                    'metapolicy_struct' => $metapolicy_struct
                );

                  $meta_exists = $post_meta->getAll();
                clearWpDb();

                if ($meta_exists) {
                    $hotel_meta_update = $post_meta->update();
                    clearWpDb();
                } else {
                    $hotel_meta_create = $post_meta->create();
                    clearWpDb();
                }

                $singleHotelEnd = microtime(true);

                $singleHotelTotal = $singleHotelEnd - $singleHotelStart;

                $regionTotal += $singleHotelTotal;

                logToRegionFile("Completed processing hotel: " . $hotel_name . " in " . number_format($singleHotelTotal, 4), $current_country_code, "INFO");

                // Final flush of any pending results
                clearWpDb();

                //sleep(15);

              } catch(\Exception $ex) {
                logToRegionFile("CRITICAL ERROR: " . $ex->getMessage(), $current_country_code, "ERROR");
                logToRegionFile("Error stack trace: " . $ex->getTraceAsString(), $current_country_code, "ERROR");
                echo 'error check logs';
                logToRegionFile(print_r($ex, true), $current_country_code, "ERROR");

                // Make sure to flush any pending results even on error
                clearWpDb();
              }
            }

           // sleep(10);

        }
        // End of foreach

        logToRegionFile("Successfully processed all hotels in Country: " . $country . " in " . number_format($regionTotal, 4), "global", "INFO");

    } catch(\Exception $ex) {
        logToRegionFile("CRITICAL ERROR: " . $ex->getMessage(), "global", "ERROR");
        logToRegionFile("Error stack trace: " . $ex->getTraceAsString(), "global", "ERROR");
        echo 'error check logs';
        logToRegionFile(print_r($ex, true), "global", "ERROR");

        // Make sure to flush any pending results even on fatal error
        if (isset($wpdb) && $wpdb) {
            clearWpDb();
        }
    }

?>