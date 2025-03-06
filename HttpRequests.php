<?php

function checkHotel($data_hotel) {
    // Load the state from JSON
    $state = json_decode(file_get_contents('region_state.json'), true);

    // Default state if no state exists
    if (!$state) {
        $state = [
            'last_region_index' => -1, 
            'last_hotel_key_index' => -1, 
            'last_hotel_index' => -1
        ];
    }

    $regions = array_keys($data_hotel);
    $lastRegionIndex = $state['last_region_index'];
    $lastHotelKeyIndex = $state['last_hotel_key_index'];
    $lastHotelIndex = $state['last_hotel_index'];

    // Move to the next hotel in the current key
    $nextHotelIndex = $lastHotelIndex + 1;

    // If there is no valid region or the last region is exhausted, go to the next region
    if ($lastRegionIndex < 0 || !isset($regions[$lastRegionIndex])) {
        // Initialize the first region
        $nextRegionIndex = 0;
        $nextHotelKeyIndex = 0;
        $nextHotelIndex = 0;
    } else {
        // Get current region ID and its hotel keys
        $currentRegionId = $regions[$lastRegionIndex];
        $hotelKeys = array_keys($data_hotel[$currentRegionId]);

        // Check if the current hotel key is exhausted
        if (!isset($hotelKeys[$lastHotelKeyIndex]) || $nextHotelIndex >= count($data_hotel[$currentRegionId][$hotelKeys[$lastHotelKeyIndex]])) {
            // Move to the next hotel key
            $nextHotelKeyIndex = $lastHotelKeyIndex + 1;
            $nextHotelIndex = 0;

            // Check if all hotel keys in the current region are exhausted
            if ($nextHotelKeyIndex >= count($hotelKeys)) {
                // Move to the next region if all hotel keys are exhausted
                $nextRegionIndex = $lastRegionIndex + 1;
                $nextHotelKeyIndex = 0;
                $nextHotelIndex = 0;

                // If all regions are exhausted, loop back to the first region
                if ($nextRegionIndex >= count($regions)) {
                    $nextRegionIndex = 0;
                }
            } else {
                // Stay in the same region but move to the next hotel key
                $nextRegionIndex = $lastRegionIndex;
            }
        } else {
            // Stay in the same region and hotel key
            $nextRegionIndex = $lastRegionIndex;
            $nextHotelKeyIndex = $lastHotelKeyIndex;
        }
    }

    // Fetch the next region, hotel key, and hotel
    $nextRegionId = $regions[$nextRegionIndex];
    $hotelKeys = array_keys($data_hotel[$nextRegionId]);

    if (isset($hotelKeys[$nextHotelKeyIndex])) {
        $nextHotelKey = $hotelKeys[$nextHotelKeyIndex];
        if (isset($data_hotel[$nextRegionId][$nextHotelKey][$nextHotelIndex])) {
            $hotelToProcess = $data_hotel[$nextRegionId][$nextHotelKey][$nextHotelIndex];
        } else {
            $hotelToProcess = null;
        }
    } else {
        $hotelToProcess = null;
    }

    // Update the state in the JSON file
    file_put_contents('region_state.json', json_encode([
        'last_region_index' => $nextRegionIndex,
        'last_hotel_key_index' => $nextHotelKeyIndex,
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