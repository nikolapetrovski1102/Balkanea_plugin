<?php

//require('../wp-includes/wp-db.php');

    global $wpdb;

    $prefix = 'Y7FXuNUTt';
    $url = 'https://api.worldota.net/api/b2b/v3/hotel/info/?data={"id":"lagomandra_hotel_and_spa","language":"en"}';
    $keyId = '7788';
    $apiKey = 'e6a79dc0-c452-48e0-828d-d37614165e39';

    $credentials = base64_encode($keyId . ':' . $apiKey);
    $headers = array(
        'Authorization: Basic ' . $credentials,
    );

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Disable SSL verification

    $response = curl_exec($ch);
 
    $data2 = json_decode($response, true);

    $current_date_time = date('Y-m-d H:i:s');

    $post_content = '';
    $post_title = $data2['data']['name'];
    $post_excerpt = $data2['data']['metapolicy_extra_info'];
    $address = $data2['data']['address'];
    $star_rating = $data2['data']['star_rating'];
    $latitude = $data2['data']['latitude'];
    $longitude = $data2['data']['longitude'];

    foreach ($data2['data']['description_struct'] as $content){
        foreach ($content['paragraphs'] as $paragraph){
            $post_content .= $paragraph . '<br><br>';
        }
    }

    try {
        $wpdb->insert(
            $prefix . '_posts',
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
                'post_name' => $post_title,
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
        echo 'Data inserted successfully';
    } catch (Exception $e) {
        echo 'Caught exception: ',  $e->getMessage(), "\n";
    }

    $post_id = $wpdb->get_results("SELECT ID FROM " . $prefix . "_posts WHERE post_title = '" . $post_title . "'");

    $wpdb->insert(
        $prefix . '_postmeta',
        array(
            'post_id' => $post_id,
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

    curl_close($ch);

?>