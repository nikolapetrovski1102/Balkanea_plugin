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
    $data2 = $data2['data'];

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

    try {
    $counter = 0;
    foreach ($data2['images'] as $img){
        $img_url = str_replace('{size}', '640x400', $img);
        $counter++;
        $wpdb->insert(
            $prefix . '_posts',
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
                'post_name' => $post_id_name . ' ' . $counter,
                'to_ping' => '',
                'pinged' => '',
                'post_modified' => $current_date_time,
                'post_modified_gmt' => $current_date_time,
                'post_content_filtered' => '',
                'post_parent' => $post_id,
                'guid' => $img_url,
                'menu_order' => 0,
                'post_type' => 'attachment',
                'post_mime_type' => 'image/jpeg',
                'comment_count' => 0
            )
        );
    }
        echo 'Data inserted successfully';
    } catch (Exception $e) {
        echo 'Caught exception: ',  $e->getMessage(), "\n";
    }

    curl_close($ch);

?>