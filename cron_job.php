<?php

    $path = $_SERVER['DOCUMENT_ROOT']; 
    include_once $path . '/wp-load.php';

    global $wpdb;

    $wpdb->show_errors();

    $hotels_by_region_url = 'https://api.worldota.net/api/b2b/v3/search/serp/region/';


    $prefix = $wpdb->prefix;
    $keyId = '7788';
    $apiKey = 'e6a79dc0-c452-48e0-828d-d37614165e39';

    $credentials = base64_encode($keyId . ':' . $apiKey);
    $headers = array(
        'Authorization: Basic ' . $credentials,
        'Content-Type: application/json'
    );

    $checkin = date("Y-m-d", strtotime("+2 day"));
    $checkout = date("Y-m-d", strtotime("+5 day"));

    $timestamp_checkout = strtotime($checkout);
    $timestamp_checkin = strtotime($checkin);

    $body_data = array(
        "checkin" => $checkin,
        "checkout" => $checkout,
        "residency" => "gr",
        "language" => "en",
        "guests" => array(
            array(
                "adults" => 2,
                "children" => array()
            )
        ),
        "region_id" => 603217,
        "currency" => "EUR"
    );
    
    $json_data = json_encode($body_data);
    
    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL, $hotels_by_region_url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $json_data);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

    $result = curl_exec($ch);
    
    curl_close($ch);
    
    $data1;

    if ($result === false) {
        echo 'cURL Error: ' . curl_error($ch);
    } else {
        $res = json_decode($result, true);
        $data1 = $res['data']['hotels'];
    }
    
    $room_name = '';
    $meal = '';
    $daily_price = 0;

    $hotel_id = $data1[0]['id'];
    print_r('<br>');


    $url = 'https://api.worldota.net/api/b2b/v3/hotel/info/';

    $data_hotel = array(
        'id' => $hotel_id,
        'language' => 'en'
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
    
    $data2;

    if ($response === false) {
        echo 'cURL Error: ' . curl_error($ci);
    }

    $data2 = json_decode($response, true)['data'];

    $current_date_time = date('Y-m-d H:i:s');

    $post_content = '';
    $post_excerpt = '';
    $post_title = $data2['name'];
    $address = $data2['address'];
    $star_rating = $data2['star_rating'];
    $latitude = $data2['latitude'];
    $longitude = $data2['longitude'];
    $post_id_name = $data2['id'];
    $img_urls = '';

    foreach ($data2['description_struct'] as $content){
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
        if ($wpdb->last_error) {
            throw new Exception($wpdb->last_error);
        }
        else
            echo '<br>Data for posts inserted successfully<br>';
    } catch (Exception $e) {
        echo 'Caught exception: ',  $e->getMessage(), "\n";
    }

    $post_id = $wpdb->get_results("SELECT ID FROM " . $prefix . "posts WHERE post_title = '" . $post_title . "'");

    try{
        $wpdb->insert(
            $prefix . 'st_hotel',
            array(
                'post_id' => (int)$post_id[0]->ID,
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
        foreach ($data1[0]['rates'] as $rooms){
            $room_name = $rooms['room_name'];
            $meal = $rooms['meal'];
            $daily_price = $rooms['daily_prices'][0];
            $counter++;
            $wpdb->insert(
                $prefix . 'st_room_availability',
                array(
                    'post_id' => (int)$post_id[0]->ID,
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

    // try {
    // $counter = 0;
    // foreach ($data2['images'] as $img){
    //     $img_url = str_replace('{size}', '640x400', $img);
    //     $counter++;
    //     $wpdb->insert(
    //         $prefix . '_posts',
    //         array(
    //             'post_author' => 14,
    //             'post_date' => $current_date_time,
    //             'post_date_gmt' => $current_date_time,
    //             'post_content' => '',
    //             'post_title' => $post_title . ' (' . $counter . ')',
    //             'post_excerpt' => '',
    //             'post_status' => 'inherit',
    //             'comment_status' => 'open',
    //             'ping_status' => 'closed',
    //             'post_password' => '',
    //             'post_name' => $post_id_name . '_' . $counter,
    //             'to_ping' => '',
    //             'pinged' => '',
    //             'post_modified' => $current_date_time,
    //             'post_modified_gmt' => $current_date_time,
    //             'post_content_filtered' => '',
    //             'post_parent' => $post_id,
    //             'guid' => $img_url,
    //             'menu_order' => 0,
    //             'post_type' => 'attachment',
    //             'post_mime_type' => 'image/jpeg',
    //             'comment_count' => 0
    //         )
    //     );
    // }
    //     echo '<br>Data inserted successfully';
    // } catch (Exception $e) {
    //     echo 'Caught exception: ',  $e->getMessage(), "\n";
    // }

?>