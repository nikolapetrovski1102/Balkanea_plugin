<?php

function checkHotel($data_hotel) {
    $state = json_decode(file_get_contents('region_state.json'), true);

    if (!$state) {
        $state = ['last_region_index' => -1, 'last_hotel_index' => -1];
    }

    $regions = array_keys($data_hotel);
    $lastRegionIndex = $state['last_region_index'];
    $lastHotelIndex = $state['last_hotel_index'];

    $nextHotelIndex = $lastHotelIndex + 1;
    
    if ($lastRegionIndex < 0 || !isset($regions[$lastRegionIndex]) || $nextHotelIndex >= count($data_hotel[$regions[$lastRegionIndex]])) {
        $nextRegionIndex = ($lastRegionIndex + 1) % count($regions);
        $nextHotelIndex = 0;
    } else {
        $nextRegionIndex = $lastRegionIndex;
    }

    if (isset($regions[$nextRegionIndex])) {
        $nextRegionId = $regions[$nextRegionIndex];
        $hotelsInRegion = $data_hotel[$nextRegionId];

        if (isset($hotelsInRegion[$nextHotelIndex])) {
            $hotelToProcess = $hotelsInRegion[$nextHotelIndex];
        } else {
            // Hotel index invalid, return null
            $hotelToProcess = null;
        }
    } else {
        // Region index invalid, return null
        $hotelToProcess = null;
    }

    // Update the state
    file_put_contents('region_state.json', json_encode([
        'last_region_index' => $nextRegionIndex,
        'last_hotel_index' => $nextHotelIndex
    ]));

    return $hotelToProcess;
}

function getHotelPrice($data_hotel, $headers) {
    $hotel_id = checkHotel($data_hotel);

    $hotels_by_region_url = 'https://api.worldota.net/api/b2b/v3/search/hp/';

    $checkin = date("Y-m-d", strtotime("+54 day"));
    $checkout = date("Y-m-d", strtotime("+62 day"));

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
        "id" => $hotel_id,
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

    if ($result === false) 
        return curl_error($ch);
    else
        return json_decode($result, true);
}

function getHotelDetails($hotel_data, $headers){
    $hotel_id = checkHotel($hotel_data);

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

    if ($response === false)
        return curl_error($ci);
    else
        return json_decode($response, true)['data'];
}

?>