<?php

/**
 * Manages the state of hotel processing and returns the next hotel to be processed
 * Uses a JSON file to maintain state between executions
 * 
 * @param array $data_hotel Hierarchical array of hotels organized by regions
 * @return mixed Returns the next hotel ID to process or null if no hotels are available
 */

function trackNextRegion() {
    error_log("Tracking next region...");
    $stateFile = 'region_tracker_state.json';
    $csvFile = realpath(__DIR__) . '/data/regions.csv'; // Adjust path as needed
    
    // Load the current state or initialize if it doesn't exist
    $state = json_decode(@file_get_contents($stateFile), true) ?: ['last_region_index' => -1];
    
    // Read and parse CSV file
    $data_hotel = [];
    if (($handle = fopen($csvFile, "r")) !== FALSE) {
        $header = fgetcsv($handle); // Skip header row
        while (($data = fgetcsv($handle)) !== FALSE) {
            $region = $data[0];
            $hotelId = $data[1];
            
            if (!isset($data_hotel[$region])) {
                $data_hotel[$region] = [];
            }
            $data_hotel[$region][] = $hotelId;
        }
        fclose($handle);
    } else {
        return json_encode(['error' => 'Could not open CSV file.']);
    }
    
    // Extract region names
    $regions = array_keys($data_hotel);
    if (empty($regions)) {
        return json_encode(['error' => 'No regions available.']);
    }
    
    // Calculate the next region index (cycle through regions)
    $nextIndex = ($state['last_region_index'] + 1) % count($regions);
    $countryId = $regions[$nextIndex];
    $hotelIds = $data_hotel[$countryId];
    
    // Update the state
    $state['last_region_index'] = $nextIndex;
    file_put_contents($stateFile, json_encode($state));
    
    // Log for debugging
    error_log("Selected region: $countryId with " . count($hotelIds) . " hotels");
    
    // Return the region data as JSON
    return json_encode([
        'country' => $countryId,
        'regions' => $hotelIds
    ]);
}

function trackNextRegion_phpFIle() {
    // Load regions
    error_log("Tracking next region...");
    $stateFile = 'region_tracker_state.json';
    
    // Load the current state or initialize if it doesn't exist
    $state = json_decode(@file_get_contents($stateFile), true) ?: ['last_region_index' => -1];
    
    // Include the region data
    require_once realpath(__DIR__) . '/data/track_region.php';
    
    // Extract region IDs
    $regions = array_keys($data_hotel);
    if (empty($regions)) {
        return json_encode(['error' => 'No regions available.']);
    }
    
    // Calculate the next region index (cycle through regions)
    $nextIndex = ($state['last_region_index'] + 1) % count($regions);
    $countryId = $regions[$nextIndex];
    $regions = $data_hotel[$countryId];
    
    // Update the state
    $state['last_region_index'] = $nextIndex;
    file_put_contents($stateFile, json_encode($state));
    
    // Return the region data as JSON
    return json_encode([
        'country' => $countryId,
        'regions' => $regions
    ]);

}

/**
 * Fetches hotel pricing information from the WorldOta API
 * Makes a POST request to get hotel prices for specific dates and guest configuration
 * 
 * @param array $data_hotel Array containing hotel data
 * @param array $headers HTTP headers for API authentication
 * @return array|string Returns API response as array or error message
 */
function getHotelPrice($region, $headers) {

    $hotels_by_region_url = 'https://api.worldota.net/api/b2b/v3/search/hp/';

    // Set check-in date to 54 days from now and checkout to 62 days (8-day stay)
    $checkin = date("Y-m-d", strtotime("+54 day"));
    $checkout = date("Y-m-d", strtotime("+62 day"));

    // Prepare request body with search parameters
    $body_data = array(
        "checkin" => $checkin,
        "checkout" => $checkout,
        "residency" => "gr",              // Set residency to Greece
        "language" => "en",               // Set language to English
        "guests" => array(
            array(
                "adults" => 2,            // Search for 2 adults
                "children" => array()      // No children
            )
        ),
        "id" => $hotel_id,
        "currency" => "EUR"               // Prices in Euros
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

/**
 * Retrieves detailed information about a specific hotel
 * Makes a POST request to fetch comprehensive hotel details including amenities,
 * location, and other property information
 * 
 * @param array $hotel_data Array containing hotel data
 * @param array $headers HTTP headers for API authentication
 * @return array|string Returns hotel details as array or error message
 */
function getHotelDetails($region, $headers){
    try{
        $url = 'http://cyberlink-001-site33.atempurl.com/api/ExtractHotels/process-by-region';
        
        $data = array(
            "region_id" => $region
        );
        
        $jsonData = json_encode($data);
        
        $ci = curl_init();
        
        curl_setopt($ci, CURLOPT_URL, $url);
        curl_setopt($ci, CURLOPT_POST, true);
        curl_setopt($ci, CURLOPT_POSTFIELDS, $jsonData);
        curl_setopt($ci, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ci, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ci, CURLOPT_SSL_VERIFYPEER, false);
        
        curl_setopt($ci, CURLOPT_TIMEOUT, 300);
        curl_setopt($ci, CURLOPT_TIMEOUT_MS, 300000);
        curl_setopt($ci, CURLOPT_BUFFERSIZE, 1280000);
        curl_setopt($ci, CURLOPT_LOW_SPEED_LIMIT, 1);
        curl_setopt($ci, CURLOPT_LOW_SPEED_TIME, 60);
        
        ini_set('memory_limit', '-1');
        set_time_limit(0);
        
        $response = curl_exec($ci);
        
        if (curl_errno($ci)) {
            echo 'CURL Error: ' . curl_error($ci);
        } else {
            $httpCode = curl_getinfo($ci, CURLINFO_HTTP_CODE);
            return json_decode($response, true);;
        }
    }catch(\Exception $ex){
        error_log("Error when calling API " . print_r($ex->getMessage(), true));
    }
    
    curl_close($ci);
    // $hotel_id = $hotel_name;

    // $url = 'https://api.worldota.net/api/b2b/v3/hotel/info/';

    // error_log("Executing hotel" . $hotel_id);

    // $data_hotel = array(
    //     'id' => $hotel_id,
    //     'language' => 'en'                // Set response language to English
    // );

    // $json_data_hotel = json_encode($data_hotel);
    
    // $ci = curl_init();
    
    // curl_setopt($ci, CURLOPT_URL, $url);
    // curl_setopt($ci, CURLOPT_POST, 1);
    // curl_setopt($ci, CURLOPT_RETURNTRANSFER, true);
    // curl_setopt($ci, CURLOPT_POSTFIELDS, $json_data_hotel);
    // curl_setopt($ci, CURLOPT_HTTPHEADER, $headers);
    // curl_setopt($ci, CURLOPT_RETURNTRANSFER, true);
    // curl_setopt($ci, CURLOPT_SSL_VERIFYPEER, false);
    
    // $response = curl_exec($ci);

    // curl_close($ci);

    // if ($response === false)
    //     return curl_error($ci);
    // else
    //     return json_decode($response, true)['data'];
}

?>

/**
 * For testing in a local environment
 * @param $region
 * @return mixed
 */
function getHotelDetailsLocal($region){
    $jsonFilePath = 'data/test_data.json';
    $jsonContent = json_decode(file_get_contents($jsonFilePath), true);
    return $jsonContent['outputFile'][$region];
}
?>
