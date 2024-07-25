<?php

    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);

    $path = $_SERVER['DOCUMENT_ROOT']; 
    include './data/data.php';
    include './data/track_data.php';
    include './HttpRequests.php';
    include_once $path . '/wp-load.php';

    global $wpdb;

    $wpdb->show_errors();
    $prefix = $wpdb->prefix;

    $keyId = '7788';
    $apiKey = 'e6a79dc0-c452-48e0-828d-d37614165e39';

    $credentials = base64_encode($keyId . ':' . $apiKey);
    $headers = array(
        'Authorization: Basic ' . $credentials,
        'Content-Type: application/json'
    );

    // Getting price of a hotel api
    $response_hotel = getHotelPrice($data_hotel, $headers);

    if ($response_hotel['data'] == null || $response_hotel['data']['hotels'] == null) {
        exit('No data found for <strong>' . $response_hotel['debug']['request']['id'] . '</strong>');
    }

    $checkin = $response_hotel['debug']['request']['checkin'];
    $checkout = $response_hotel['debug']['request']['checkin'];

    $timestamp_checkout = strtotime($checkout);
    $timestamp_checkin = strtotime($checkin);

    $room_name = '';
    $meal = '';
    $daily_price = 0;

    $hotel = getHotelDetails($response_hotel['data']['hotels'][0]['id'], $headers);

    $amenity_array = array();

    print_r('<pre>');

    if ($hotel != null) {
        
        foreach ($hotel['amenity_groups'] as $group) {
            foreach ($group['amenities'] as $amenity) {

                if ($amenity == '24-hour reception')
                    $amenity = '24-hour front desk';
                else if ($amenity == 'Free Wi-Fi')
                    $amenity = 'Free WiFi';

                $query_terms = $wpdb->prepare("SELECT term_id FROM " . $prefix . "terms WHERE name LIKE %s", '%' . $wpdb->esc_like($amenity) . '%');
                $amenity_found_terms = $wpdb->get_results($query_terms);

                if ($amenity_found_terms) {
                    foreach ($amenity_found_terms as $term) {
                        $query_term_taxonomy = $wpdb->prepare("SELECT term_taxonomy_id FROM " . $prefix . "term_taxonomy WHERE term_id = %d AND taxonomy = 'hotel-facilities'", $term->term_id);
                        $amenity_found_term_taxonomy = $wpdb->get_results($query_term_taxonomy);

                        if ($amenity_found_term_taxonomy) {
                            foreach ($amenity_found_term_taxonomy as $taxonomy) {
                                print_r($taxonomy->term_taxonomy_id . ' found </br>');
                                array_push($amenity_array, $taxonomy->term_taxonomy_id);
                            }
                        } else {
                            print_r('Term taxonomy not found for term_id: ' . $term->term_id . '</br>');
                        }
                    }
                } else {
                    print_r($amenity . ' not found </br>');
                }
            }
        }
    }

    print_r('</pre>');


    $post_exists = $wpdb->get_row("SELECT post_title FROM " . $prefix . "posts WHERE post_name = '" . $hotel['id'] . "'");

    if ($post_exists) {
        exit('Post ' . $hotel['id'] . ' already exists');
    }

    $current_date_time = date('Y-m-d H:i:s');

    $post_content = '';
    $post_excerpt = '';
    $post_title = $hotel['name'];
    $address = $hotel['address'];
    $star_rating = $hotel['star_rating'];
    $latitude = $hotel['latitude'];
    $longitude = $hotel['longitude'];
    $post_id_name = $hotel['id'];
    $img_urls = '';
    

    foreach ($hotel['description_struct'] as $content){
        if ($content['title'] == 'Location'){
            foreach ($content['paragraphs'] as $paragraph){
                $post_excerpt .= $paragraph . '<br><br>';
            }
        }
        else{
            foreach ($content['paragraphs'] as $paragraph){
                $post_content .= $paragraph . '<br><br>';
            }
        }
    }

    $post_id;

    try {
        $wpdb->insert(
            $prefix . 'posts',
            array(
                'post_author' => 14,
                'post_date' => $current_date_time,
                'post_date_gmt' => $current_date_time,
                'post_content' => $post_content,
                'post_title' => $post_title,
                'post_excerpt' => $post_excerpt,
                'post_status' => 'draft',
                'comment_status' => 'open',
                'ping_status' => 'open',
                'post_password' => '',
                'post_name' => $post_id_name,
                'to_ping' => '',
                'pinged' => '',
                'post_modified' => $current_date_time,
                'post_modified_gmt' => $current_date_time,
                'post_content_filtered' => '',
                'post_parent' => 0,
                'guid' => '',
                'menu_order' => 0,
                'post_type' => 'st_hotel',
                'post_mime_type' => '',
                'comment_count' => 0
            )
        );
        if ($wpdb->last_error)
            throw new Exception($wpdb->last_error);
        else
            echo '<br>Data for posts inserted successfully<br>';

        $post_id = $wpdb->insert_id;
        echo 'Inserted post ID: ' . $post_id . '<br>';

    } catch (Exception $e) {
        echo 'Caught exception: ',  $e->getMessage(), "\n";
    }

    $prices = array();

    $price_avg = 0;
    $price_min = 0;

    $rooms_array = array();

    try {
        $response_hotel = $response_hotel['data']['hotels'][0];
        $counter = 0;
        
        foreach ($response_hotel['rates'] as $rooms) {
            $room_name = $rooms['room_name'];
            $meal = $rooms['meal'];
            $daily_price = $rooms['daily_prices'][0];

            array_push($prices, (int)$daily_price);

            $result = $wpdb->insert(
                $prefix . 'posts',
                array(
                    'post_author' => 14,
                    'post_date' => $current_date_time,
                    'post_date_gmt' => $current_date_time,
                    'post_content' => $post_content,
                    'post_title' => $room_name,
                    'post_excerpt' => '',
                    'post_status' => 'draft',
                    'comment_status' => 'open',
                    'ping_status' => 'closed',
                    'post_password' => '',
                    'post_name' => $post_id_name . '-' . $room_name,
                    'to_ping' => '',
                    'pinged' => '',
                    'post_modified' => $current_date_time,
                    'post_modified_gmt' => $current_date_time,
                    'post_content_filtered' => '',
                    'post_parent' => $post_id,
                    'guid' => '',
                    'menu_order' => 0,
                    'post_type' => 'hotel_room',
                    'post_mime_type' => '',
                    'comment_count' => 0
                )
            );

            if ($result === false) {
                error_log('wpdb last error: ' . $wpdb->last_error);
            } else {
                $post_room_id = $wpdb->insert_id;
                echo 'Inserted post ID: ' . $post_room_id . '<br>';
                
                $counter++;
                
                $wpdb->insert(
                    $prefix . 'hotel_room',
                    array(
                        'post_id' => $post_room_id,
                        'room_parent' => $post_id,
                        'multi_location' => '',
                        'id_location' => '',
                        'address' => $address,
                        'allow_full_day' => 'off',
                        'price' => $daily_price,
                        'number_room' => $counter,
                        'discount_rate' => '',
                        'adult_number' => 2,
                        'child_number' => 0,
                        'status' => 'draft',
                        'adult_price' => '',
                        'child_price' => '',
                    )
                );
            }
        }

        if ($wpdb->last_error) {
            echo 'wpdb last error: ' . $wpdb->last_error . '<br>';
            error_log('wpdb last error: ' . $wpdb->last_error);
        } else {
            echo '<br>Data for posts hotel room inserted successfully<br>';
        }

    } catch (Exception $e) {
        echo 'Caught exception: ',  $e->getMessage(), "\n";
    }


    try{
        $wpdb->insert(
            $prefix . 'st_hotel',
            array(
                'post_id' => (int)$post_id,
                'multi_location' => '_14848_,_15095_',
                'id_location' => '',
                'address' => $address,
                'allow_full_day' => 'on',
                'rate_review' => 0,
                'hotel_star' => $star_rating,
                'price_avg' => $price_avg,
                'min_price' => $price_min,
                'hotel_booking_period' => 0,
                'map_lat' => $latitude,
                'map_lng' => $longitude,
                'is_sale_schedule' => NULL,
                'post_origin' => NULL,
                'is_featured' => 'off'
            )
        );
        if ($wpdb->last_error) {
            throw new Exception($wpdb->last_error);
        }
        else
            echo '<br>Data for st_hotel inserted successfully<br>';
    }
    catch (Exception $e) {
        echo 'Caught exception: ',  $e->getMessage(), "\n";
    }

    // try{
    //     $counter = 0;
    //         $response_hotel = $response_hotel['data']['hotels'][0];
    //         foreach ($response_hotel['rates'] as $rooms){
    //         $room_name = $rooms['room_name'];
    //         $meal = $rooms['meal'];
    //         $daily_price = $rooms['daily_prices'][0];
    //         array_push($prices, 1(int)$daily_price);
    //         $counter++;
    //         $wpdb->insert(
    //             $prefix . 'st_room_availability',
    //             array(
    //                 'post_id' => (int)$post_id,
    //                 'check_in' => (int)$timestamp_checkin + $counter,
    //                 'check_out' => (int)$timestamp_checkout + $counter,
    //                 'number' => 0,
    //                 'post_type' => 'hotel_room',
    //                 'price' => $daily_price,
    //                 'status' => 'available',
    //                 'priority' => NULL,
    //                 'number_booked' => 0,
    //                 'parent_id' => 0,
    //                 'allow_full_day' => 'on',
    //                 'number_end' => NULL,
    //                 'booking_period' => 0,
    //                 'is_base' => 1,
    //                 'adult_number' => 2,
    //                 'child_number' => 0,
    //                 'adult_price' => 0,
    //                 'child_price' => 0,
    //             )
    //         );
    //         if ($wpdb->last_error) {
    //             throw new Exception($wpdb->last_error);
    //         }
    //         else
    //             echo '<br>Data for ' . $room_name . ' inserted successfully<br>';
    //     }
    // }
    // catch(Exception $e){
    //     echo 'Caught exception: ',  $e->getMessage(), "\n";
    // }

    $price_avg = array_sum($prices) / count($prices);

    $price_min = min($prices);

    $post_image_array_ids = '';

    try {
        $directory = '/home/balkanea/public_html/wp-content/uploads/2024/07';
        $image_origin_url = 'https://balkanea.com/wp-content/uploads/2024/07/';
        $counter = 0;
        $post_image_array_ids = '';
    
        foreach ($hotel['images'] as $img) {
            if (!file_exists($directory)) {
                mkdir($directory, 0777, true);
            }
    
            $img_url = str_replace('{size}', '640x400', $img);
    
            $image_path = $directory . '/' . basename($img_url);
            file_put_contents($image_path, file_get_contents($img_url));
    
            $image_guid = $image_origin_url . basename($img_url);
    
            $counter++;
            $wpdb->insert(
                $prefix . 'posts',
                array(
                    'post_author' => 14,
                    'post_date' => $current_date_time,
                    'post_date_gmt' => $current_date_time,
                    'post_content' => '',
                    'post_title' => $post_title . ' (' . $counter . ')',
                    'post_excerpt' => '',
                    'post_status' => 'inherit',
                    'comment_status' => 'open',
                    'ping_status' => 'closed',
                    'post_password' => '',
                    'post_name' => $post_id_name . '-' . $counter,
                    'to_ping' => '',
                    'pinged' => '',
                    'post_modified' => $current_date_time,
                    'post_modified_gmt' => $current_date_time,
                    'post_content_filtered' => '',
                    'post_parent' => $post_id,
                    'guid' => $image_guid,
                    'menu_order' => 0,
                    'post_type' => 'attachment',
                    'post_mime_type' => 'image/jpeg',
                    'comment_count' => 0
                )
            );
    
            $post_image_array_ids .= $wpdb->insert_id . ',';
    
            $photo_metadata = array(
                'width' => 640,
                'height' => 400,
                'file' => '2024/07/' . basename($image_path),
                'filesize' => filesize($image_path),
                'sizes' => array(),
                'image_meta' => array(
                    'aperture' => '0',
                    'credit' => '',
                    'camera' => '',
                    'caption' => '',
                    'created_timestamp' => '0',
                    'copyright' => '',
                    'focal_length' => '0',
                    'iso' => '0',
                    'shutter_speed' => '0',
                    'title' => '',
                    'orientation' => '1',
                    'keywords' => array()
                )
            );
    
            $photo_metadata_serialized = serialize($photo_metadata);
            
            $wpdb->insert(
                $prefix . 'postmeta',
                array(
                    'post_id' => $wpdb->insert_id,
                    'meta_key' => '_wp_attached_file',
                    'meta_value' => '2024/07/' . basename($image_path)
                )
            );
    
            $wpdb->insert(
                $prefix . 'postmeta',
                array(
                    'post_id' => $wpdb->insert_id,
                    'meta_key' => '_wp_attachment_metadata',
                    'meta_value' => $photo_metadata_serialized
                )
            );
        }
    
        echo '<br>Data inserted successfully';
        $post_image_array_ids = rtrim($post_image_array_ids, ',');
    } catch (Exception $e) {
        echo 'Caught exception: ', $e->getMessage(), "\n";
    }    

    $meta_values = array(
        'rate_review' => 0,
        'price_avg' => $price_avg,
        'min_price' => $price_min,
        'meta_value' => 'classic-editor',
        '_edit_lock' => '1720094804:14',
        '_edit_last' => 14,
        '_tve_js_modules_gutenberg' => 'a:0:{}',
        'st_google_map' => 'a:4:{s:3:"lat";s:17:"' . $latitude . '";s:3:"lng";s:17:"' . $longitude . '";s:4:"zoom";s:2:"13";s:4:"type";s:0:"";}',
        'multi_location' => '_14848_,_15095_',
        'address' => $address,
        'is_featured' => 'off',
        'st_hotel_external_booking' => 'off',
        'hotel_star' => $star_rating,
        'is_auto_caculate' => 'on',
        'allow_full_day' => 'on',
        'check_in_time' => '12:00 - 20:00',
        'check_out_time' => '10:00',
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
        '_yoast_wpseo_estimated-reading-time-minutes' => 2,
        'hotel_layout_style' => 5,
        'hotel_policy' => 'a:1:{i:0;a:2:{s:5:"title";s:0:"";s:18:"policy_description";s:' . strlen($hotel['metapolicy_extra_info']) . ':"' . $hotel['metapolicy_extra_info'] . '";}}',
        '_thumbnail_id' => $post_image_array_ids != null ? explode(",", $post_image_array_ids)[0] : '',
        'gallery' => $post_image_array_ids,
        '_wp_old_date' => date('YYYY-mm-dd')
    );

    try{
        foreach ($meta_values as $meta_key => $meta_value) {
            $wpdb->insert(
                $prefix . 'postmeta',
                array(
                    'post_id' => $post_id,
                    'meta_key' => $meta_key,
                    'meta_value' => $meta_value,
                )
            );
        }    
    }
    catch(Exception $e){
        echo 'Caught exception: ',  $e->getMessage(), "\n";
    }

    try{
        foreach ($amenity_array as $hotel_facility_number) {
        $wpdb->insert(
            $prefix . 'term_relationships',
                array(
                    'object_id' => $post_id,
                    'term_taxonomy_id' => $hotel_facility_number,
                    'term_order' => 0
                )
            );
        }

        echo '<br>hotel facilities inserted successfully';

    }catch(Exception $ex){
        echo 'Caught exception: ',  $ex->getMessage(), "\n";
    }

?>