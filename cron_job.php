<?php

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

    $checkin = $response_hotel['debug']['request']['checkin'];
    $checkout = $response_hotel['debug']['request']['checkin'];

    $timestamp_checkout = strtotime($checkout);
    $timestamp_checkin = strtotime($checkin);

    $room_name = '';
    $meal = '';
    $daily_price = 0;

    $hotel = getHotelDetails($response_hotel['data']['hotels'][0]['id'], $headers);

    echo '<br>' . $hotel['id'] . '<br>';

    $post_exists = $wpdb->get_row("SELECT ID FROM " . $prefix . "posts WHERE post_name = '" . $hotel['id'] . "'");

    if ($post_exists) {
        echo '<br>Post exists<br>';
        return -1;
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

    try{
        $wpdb->insert(
            $prefix . 'st_hotel',
            array(
                'post_id' => (int)$post_id,
                'multi_location' => '_14848_,_15095_',
                'id_location' => '',
                'address' => $address,
                'allow_full_day' => 'on',
                'rate_review' => $star_rating,
                'hotel_star' => 0,
                'price_avg' => 0,
                'min_price' => 0,
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
            echo '<br>Data for hotel inserted successfully<br>';
    }
    catch (Exception $e) {
        echo 'Caught exception: ',  $e->getMessage(), "\n";
    }


    try{
        $counter = 0;
            $response_hotel = $response_hotel['data']['hotels'][0];
            foreach ($response_hotel['rates'] as $rooms){
            $room_name = $rooms['room_name'];
            $meal = $rooms['meal'];
            $daily_price = $rooms['daily_prices'][0];
            $counter++;
            $wpdb->insert(
                $prefix . 'st_room_availability',
                array(
                    'post_id' => (int)$post_id,
                    'check_in' => (int)$timestamp_checkin + $counter,
                    'check_out' => (int)$timestamp_checkout + $counter,
                    'number' => 0,
                    'post_type' => 'hotel_room',
                    'price' => $daily_price,
                    'status' => 'available',
                    'priority' => NULL,
                    'number_booked' => 0,
                    'parent_id' => 0,
                    'allow_full_day' => 'on',
                    'number_end' => NULL,
                    'booking_period' => 0,
                    'is_base' => 1,
                    'adult_number' => 2,
                    'child_number' => 0,
                    'adult_price' => 0,
                    'child_price' => 0,
                )
            );
            if ($wpdb->last_error) {
                throw new Exception($wpdb->last_error);
            }
            else
                echo '<br>Data for ' . $room_name . ' inserted successfully<br>';
        }
    }
    catch(Exception $e){
        echo 'Caught exception: ',  $e->getMessage(), "\n";
    }


?>