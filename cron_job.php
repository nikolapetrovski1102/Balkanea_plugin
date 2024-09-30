<?php

use Models\Amenity;
use Models\HotelRoom;
use Models\ImageInserter;
use Models\PostMetaValues;
use Models\PostsHotel;
use Models\PostsRoom;
use Models\St_Hotel;
use Models\LocationNested;
use Models\LocationRelationship;
use Models\CurrencyModel;
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
    include './Models/RoomAvailability.php';
    include './Models/LocationRelationship.php';
    include './Models/LocationNested.php';
    include './Models/CurrencyModel.php';
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

    $hotel = getHotelDetails($data_hotel, $headers);

    if (empty($hotel)) {
        echo '<br>Hotel not found<br>';
        exit();
    }

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
    $hotel_location = $hotel['region'];

    echo '<br>' . $hotel['id'] . '<br>';

    $location_nested = new LocationNested($wpdb);

    $location_nested->location_id = $hotel_location['id'];
    $location_nested->location_country = 'GR';
    $location_nested->parent_id = 56;
    $location_nested->name = $hotel_location['name'];
    $location_nested->language = 'en';
    $location_nested->status = 'publish';

    $parent_location_id = $location_nested->create();

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
    $posts_hotel->post_status = 'publish';
    $posts_hotel->post_password = '';
    $posts_hotel->post_name = $post_id_name;
    $posts_hotel->to_ping = '';
    $posts_hotel->pinged = '';
    $posts_hotel->post_content_filtered = '';
    $posts_hotel->guid = '';
    $posts_hotel->post_mime_type = '';

    $post_id = $posts_hotel->get();

    $amenities_model = new Amenity($wpdb);
    $location_relationships = new LocationRelationship($wpdb);

    if (HotelFlag::isHotelFound()){
        echo 'Hotel found in DB<br>';
        
        $posts_hotel->id = $post_id;
        $post_response = $posts_hotel->update();

        $amenities_model->amenities = $hotel['amenity_groups'][0];
        $amenities_model->post_id = $post_id;

        $amenities = $amenities_model->getAmenities();

        $location_relationships->post_id = $post_id;
        $location_relationships->location_from = array($parent_location_id, $hotel_location['id']);
        $location_relationships->location_to = 0;
        $location_relationships->post_type = 'st_hotel';
        $location_relationships->location_type = 'multi_location';
    
        $location_relationships->insertLocationRelationship();
    }
    else{
        echo 'Hotel not found in DB<br>';
        
        $post_id = $posts_hotel->create();

        $amenities_model->amenities = $hotel['amenity_groups'][0];
        $amenities_model->post_id = $post_id;

        $amenities = $amenities_model->getAmenities();

        $location_relationships->post_id = $post_id;
        $location_relationships->location_from = array($parent_location_id, $hotel_location['id']);
        $location_relationships->location_to = 0;
        $location_relationships->post_type = 'st_hotel';
        $location_relationships->location_type = 'multi_location';

        $location_relationships->insertLocationRelationship();
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

            $posts_room->post_title = $room['name_struct']['main_name'];
            $posts_room->post_content = $post_content;
            $posts_room->post_excerpt = $post_excerpt;
            $posts_room->post_status = 'publish';
            $posts_room->post_password = '';
            $posts_room->post_name = $post_id_name . '-' . str_replace(' ', '-', $room['name_struct']['main_name']);
            $posts_room->to_ping = '';
            $posts_room->pinged = '';
            $posts_room->post_content_filtered = '';
            $posts_room->post_parent = $post_id;
            $posts_room->guid = '';
            $posts_room->post_mime_type = '';

            $posts_room_exsists = $posts_room->get();

            $post_images->hotel = $room;
            $post_images->directory_url = $hotel['id'] . '/' . str_replace(' ', '-', $room['name_struct']['main_name']);
            $post_images->post_title = $room['name_struct']['main_name'];
            $post_images->post_id_name = $post_id_name;
            $post_images->provider = 'RateHawk';
            $post_images->default_image = $hotel['images'][1];

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
                'disable_children_name' => 'off',
                'price_by_per_person' => 'off',
                'allow_full_day' => 'on',
                'price' => 0,
                'discount_type_no_day' => 'percent',
                'extra_price_unit' => 'perday',
                'adult_number' => 2,
                'children_number' => 0,
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

                $post_meta->meta_values['_thumbnail_id'] = $post_image_array_ids != null ? explode(",", $post_image_array_ids)[0] : '';
                $post_meta->meta_values['gallery'] = $post_image_array_ids ?? '';

                $posts_room->update();

                $post_meta->update();
            }
            else{
                $post_room_id = $posts_room->create();

                $post_meta->post_id = $post_room_id;
                $post_images->post_id = $post_room_id;
                
                $post_image_array_ids = $post_images->insertImages();

                $post_meta->meta_values['_thumbnail_id'] = $post_image_array_ids != null ? explode(",", $post_image_array_ids)[0] : '';
                $post_meta->meta_values['gallery'] = $post_image_array_ids ?? '';

                $post_meta->create();
            }

            $amenities_model->amenities = $room['room_amenities'];
            $amenities_model->post_id = $post_room_id;

            $amenities = $amenities_model->getRoomAmenities();

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
        error_log('Caught exception: ' . $e->getMessage());
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
    //         $response_hotel = $response_hotel['data']['hotels'][0];
    //         foreach ($response_hotel['rates'] as $rooms){
    //         $room_name = $rooms['room_name'];
    //         $meal = $rooms['meal'];
    //         $daily_price = $rooms['daily_prices'][0];
    //         array_push($prices, 1(int)$daily_price);
    //         $counter++;
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

    $post_image_array_ids = '';

    // $post_images = new ImageInserter($wpdb);

    $post_images->hotel = $hotel;
    $post_images->directory_url = $hotel['id'];
    $post_images->post_title = $post_title;
    $post_images->post_id_name = $post_id_name;
    $post_images->post_id = $post_id;
    $post_images->provider = 'RateHawk';

    $post_meta = new PostMetaValues($wpdb);

    $post_meta->post_id= $post_id;

    $post_image_array_ids = $post_images->insertImages();
    
    echo '<br>Images inserted successfully: <br>';
    print_r('<pre>');
    print_r($post_image_array_ids);
    print_r('</pre>');

    if ($post_image_array_ids == ''){
        $post_image_array_ids = $post_meta->read('gallery')->meta_value;
    }
    
    echo'<br> multi_location <br> ' . $hotel_location['id'] . ',' . $parent_location_id;

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
        'hotel_policy' => 'a:1:{i:0;a:2:{s:5:"title";s:0:"";s:18:"policy_description";s:' . strlen($hotel['metapolicy_extra_info']) . ':"' . $hotel['metapolicy_extra_info'] . '";}}',
        '_thumbnail_id' => $post_image_array_ids != null ? explode(",", $post_image_array_ids)[0] : '',
        'gallery' => $post_image_array_ids ?? '',
        '_wp_old_date' => date('YYYY-mm-dd'),
        'provider' => 'RateHawk'
    );

    if ($post_meta->get())
        $post_meta->update();
    else
        $post_meta->create();


?>