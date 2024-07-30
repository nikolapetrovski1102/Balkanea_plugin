<?php

use Models\Amenity;
use Models\HotelRoom;
use Models\ImageInserter;
use Models\PostMetaValues;
use Models\PostsHotel;
use Models\PostsRoom;
use Models\St_Hotel;
use data\HotelFlag;

    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);

    $path = $_SERVER['DOCUMENT_ROOT']; 
    include './Models/Amenity.php';
    include './Models/PostsHotel.php';
    include './Models/PostsRoom.php';
    include './Models/HotelRoom.php';
    include './Models/PostMetaValues.php';
    include './Models/St_Hotel.php';
    include './Models/ImageInsert.php';
    include './data/data.php';
    include './data/track_data.php';
    include './data/HotelFlag.php';
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
    // $response_hotel = getHotelPrice($data_hotel, $headers);

    // if ($response_hotel['data'] == null || $response_hotel['data']['hotels'] == null) {
    //     exit('No data found for <strong>' . $response_hotel['debug']['request']['id'] . '</strong>');
    // }

    // $checkin = $response_hotel['debug']['request']['checkin'];
    // $checkout = $response_hotel['debug']['request']['checkin'];

    // $timestamp_checkout = strtotime($checkout);
    // $timestamp_checkin = strtotime($checkin);

    $hotel = getHotelDetails($data_hotel, $headers);

    $posts_hotel = new PostsHotel($wpdb);

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

    $posts_hotel->post_content = $post_content;
    $posts_hotel->post_title = $hotel['name'];;
    $posts_hotel->post_excerpt = $post_excerpt;
    $posts_hotel->post_status = 'draft';
    $posts_hotel->post_password = '';
    $posts_hotel->post_name = $post_id_name;
    $posts_hotel->to_ping = '';
    $posts_hotel->pinged = '';
    $posts_hotel->post_content_filtered = '';
    $posts_hotel->guid = '';
    $posts_hotel->post_mime_type = '';

    $post_id = $posts_hotel->get();

    if (HotelFlag::isHotelFound()){
        $posts_hotel->id = $post_id;
        $post_response = $posts_hotel->update();
    }
    else{
        $post_id = $posts_hotel->create();

        // Creating instance of Amenity model
        $amenities_model = new Amenity($wpdb);

        // Assigning values
        $amenities_model->amenities = $hotel['amenity_groups'];
        $amenities_model->post_id = $post_id;

        // Getting amenities and inserting into database
        $amenities = $amenities_model->getAmenities();
    }

    $prices = array();

    $price_avg = 0;
    $price_min = 0;

    $posts_room = new PostsRoom($wpdb);

    try {
        // $response_hotel = $response_hotel['data']['hotels'][0]['rates'];
        $counter = 0;
        
        foreach ($hotel['room_groups'] as $room) {

            $posts_room->post_title = $room['name_struct']['main_name'];
            $posts_room->post_content = $post_content;
            $posts_room->post_excerpt = $post_excerpt;
            $posts_room->post_status = 'draft';
            $posts_room->post_password = '';
            $posts_room->post_name = $post_id_name . '-' . $room['name_struct']['main_name'];
            $posts_room->to_ping = '';
            $posts_room->pinged = '';
            $posts_room->post_content_filtered = '';
            $posts_room->post_parent = $post_id;
            $posts_room->guid = '';
            $posts_room->post_mime_type = '';

            $posts_room_exsists = $posts_room->get();

            if ($posts_room_exsists){
                $posts_room->id = $posts_room_exsists->ID;
                $posts_room->update();
            }
            else{
                $posts_room->create();
            }

            $hotel_room_model = new HotelRoom($wpdb);

            $hotel_room_model->post_id = $post_id == null ? $posts_room->id : $post_id;
            $hotel_room_model->room_parent = $post_id;
            $hotel_room_model->multi_location = '';
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

            $hotel_room = $hotel_room_model->get();

            if ($hotel_room)
                $hotel_room_model->update();
            else
                $hotel_room_model->create();
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

    if ($st_hotel->get())
        $st_hotel->update();
    else
        $st_hotel->create();

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

    // $price_avg = array_sum($prices) / count($prices);

    // $price_min = min($prices);

    // $post_image_array_ids = '';

    $post_images = new ImageInserter($wpdb);

    $post_images->hotel = $hotel;
    $post_images->post_title = $post_title;
    $post_images->post_id_name = $post_id_name;
    $post_images->post_id = $post_id;
    $post_images->provider = 'RateHawk';

    $post_image_array_ids = $post_images->insertImages();

    // try {
    //     $current_date_time = date('Y-m-d H:i:s');

    //     $directory = '/home/balkanea/public_html/wp-content/uploads/2024/07';
    //     $image_origin_url = 'https://balkanea.com/wp-content/uploads/2024/07/';
    //     $counter = 0;
    //     $post_image_array_ids = '';
    
    //     foreach ($hotel['images'] as $img) {
    //         if (!file_exists($directory)) {
    //             mkdir($directory, 0777, true);
    //         }
    
    //         $img_url = str_replace('{size}', '640x400', $img);
    
    //         $image_path = $directory . '/' . basename($img_url);
    //         file_put_contents($image_path, file_get_contents($img_url));
    
    //         $image_guid = $image_origin_url . basename($img_url);
    
    //         $counter++;
    //         $wpdb->insert(
    //             $prefix . 'posts',
    //             array(
    //                 'post_author' => 14,
    //                 'post_date' => $current_date_time,
    //                 'post_date_gmt' => $current_date_time,
    //                 'post_content' => '',
    //                 'post_title' => $post_title . ' (' . $counter . ')',
    //                 'post_excerpt' => '',
    //                 'post_status' => 'inherit',
    //                 'comment_status' => 'open',
    //                 'ping_status' => 'closed',
    //                 'post_password' => '',
    //                 'post_name' => $post_id_name . '-' . $counter,
    //                 'to_ping' => '',
    //                 'pinged' => '',
    //                 'post_modified' => $current_date_time,
    //                 'post_modified_gmt' => $current_date_time,
    //                 'post_content_filtered' => '',
    //                 'post_parent' => $post_id,
    //                 'guid' => $image_guid,
    //                 'menu_order' => 0,
    //                 'post_type' => 'attachment',
    //                 'post_mime_type' => 'image/jpeg',
    //                 'comment_count' => 0
    //             )
    //         );
    
    //         $post_image_array_ids .= $wpdb->insert_id . ',';
    
    //         $photo_metadata = array(
    //             'width' => 640,
    //             'height' => 400,
    //             'file' => '2024/07/' . basename($image_path),
    //             'filesize' => filesize($image_path),
    //             'sizes' => array(),
    //             'image_meta' => array(
    //                 'aperture' => '0',
    //                 'credit' => '',
    //                 'camera' => '',
    //                 'caption' => '',
    //                 'created_timestamp' => '0',
    //                 'copyright' => '',
    //                 'focal_length' => '0',
    //                 'iso' => '0',
    //                 'shutter_speed' => '0',
    //                 'title' => '',
    //                 'orientation' => '1',
    //                 'keywords' => array()
    //             )
    //         );
    
    //         $photo_metadata_serialized = serialize($photo_metadata);
            
    //         $wpdb->insert(
    //             $prefix . 'postmeta',
    //             array(
    //                 'post_id' => $wpdb->insert_id,
    //                 'meta_key' => '_wp_attached_file',
    //                 'meta_value' => '2024/07/' . basename($image_path)
    //             )
    //         );
    
    //         $wpdb->insert(
    //             $prefix . 'postmeta',
    //             array(
    //                 'post_id' => $wpdb->insert_id,
    //                 'meta_key' => '_wp_attachment_metadata',
    //                 'meta_value' => $photo_metadata_serialized
    //             )
    //         );
    //     }
    
    //     echo '<br>Data inserted successfully';
    //     $post_image_array_ids = rtrim($post_image_array_ids, ',');
    // } catch (Exception $e) {
    //     echo 'Caught exception: ', $e->getMessage(), "\n";
    // }    

    $post_meta = new PostMetaValues($wpdb);

    print_r('post images array');
    print_r($post_image_array_ids . '<br>');

    $post_meta->post_id= $post_id;
    $post_meta->meta_values = array(
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
        '_yoast_wpseo_estimated-reading-time-minutes' => NULL,
        'hotel_layout_style' => 5,
        'hotel_policy' => 'a:1:{i:0;a:2:{s:5:"title";s:0:"";s:18:"policy_description";s:' . strlen($hotel['metapolicy_extra_info']) . ':"' . $hotel['metapolicy_extra_info'] . '";}}',
        '_thumbnail_id' => $post_image_array_ids != null ? explode(",", $post_image_array_ids)[0] : '',
        'gallery' => $post_image_array_ids,
        '_wp_old_date' => date('YYYY-mm-dd')
    );

    if ($post_meta->get())
        $post_meta->update();
    else
        $post_meta->create();


?>