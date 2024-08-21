<?php

function http_query_requests($url) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, false);
    $query_response = curl_exec($ch);
    if ($query_response === false) {
        echo 'cURL Error: ' . curl_error($ch);
    } else {
        return json_decode($query_response, true);
    }
    curl_close($ch);
}

$apiUrl = 'https://api.worldota.net/api/b2b/v3/search/hp/';
$keyId = '7788';
$apiKey = 'e6a79dc0-c452-48e0-828d-d37614165e39';
$query_url = 'https://staging.balkanea.com/wp-plugin/APIs/request_query.php';

$checkin = $_GET['checkin'];
$checkout = $_GET['checkout'];
$guests = json_decode($_GET['guests'], true);
$hotel_id = $_GET['hotel_id'];
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;

$guests_adults = intval($guests[0]['adults']);
$guests_children[] = intval($guests[0]['children'][0]);

$body_data = array(
    "checkin" => $checkin,
    "checkout" => $checkout,
    "residency" => "mk",
    "language" => "en",
    "guests" => array(
        array(
            "adults" => $guests_adults,
            "children" => $guests_children[0] == 0 ? array() : $guests_children
        )
    ),
    "id" => trim($hotel_id, '"'),
    "currency" => "EUR",
);

$data = json_encode($body_data);

$ch = curl_init($apiUrl);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Authorization: Basic ' . base64_encode("$keyId:$apiKey")
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $data);

$response = curl_exec($ch);
curl_close($ch);

if ($response === false) {
    echo 'Curl error: ' . curl_error($ch);
} else {
    $response = json_decode($response, true);

    $hotel_id = $response['data']['hotels'][0]['id'];
    
    if ($response['debug']['validation_error'] != null){
        echo $response['debug']['validation_error'];
        exit();
    }
    
    if (count($response['data']['hotels']) == 0){
        echo 'No rooms';
        exit();
    }
    
    // Define the number of items per page
    $items_per_page = 5;
    
    // Calculate the starting index based on the current page
    $start_index = ($page - 1) * $items_per_page;
    $end_index = $start_index + $items_per_page;
    
    // Get the total number of rates
    $total_rates = count($response['data']['hotels'][0]['rates']);
    $total_pages = ceil($total_rates / $items_per_page);
    
    $output = '';
    $counter = 0;
    
    foreach ($response['data']['hotels'][0]['rates'] as $index => $room) {
        // Skip items until we reach the starting index
        if ($index < $start_index) {
            continue;
        }
    
        // Stop if we have processed enough items for this page
        if ($counter >= $items_per_page) {
            break;
        }
    
        $room_name = $room['room_data_trans']['main_name'];
        $room_price = $room['daily_prices'][0];
        $url_hotel_id = urlencode($hotel_id);
        $url_room_name = urlencode($room_name);
        $url_room = str_replace(' ', '-', $room_name);
        
        $check_in_date = $checkin;
        $check_out_date = $checkout;
        $nights = (new DateTime($checkout))->diff(new DateTime($checkin))->days;
        $full_price = $room_price * $nights;

        $image_room_url = "{$url_hotel_id}{$url_room}";

        $image_url = "https://staging.balkanea.com/wp-content/uploads/RateHawk/$url_hotel_id/$url_room/{$image_room_url}-1.jpg";

        $output .= <<<HTML
        <div class="item st-border-radius">
            <form class="form-booking-inpage" method="get">
                <input type="hidden" name="check_in" value="$check_in_date"/>
                <input type="hidden" name="check_out" value="$check_out_date"/>
                <input type="hidden" name="room_num_search" value="1"/>
                <input type="hidden" name="adult_number" value="$guests_adults"/>
                <input type="hidden" name="child_number" value="$guests_children[0]"/>
                <input name="action" value="hotel_add_to_cart" type="hidden">
                <!-- <input name="item_id" value="$item_id" type="hidden">
                <input name="room_id" value="$room_id" type="hidden"> -->
                <input type="hidden" name="start" value="$check_in_date"/>
                <input type="hidden" name="end" value="$check_out_date"/>
                <input type="hidden" name="is_search_room" value="1">
                <div class="row align-items-center align-items-stretch">
                    <div class="col-12 col-sm-12 col-md-12 col-lg-4">
                        <div class="image">
                            <img src="$image_url" alt="$room_name" class="img-fluid img-full st-hover-grow">
                        </div>
                    </div>
                    <div class="col-12 col-sm-12 col-md-12 col-lg-8">
                        <div class="row align-items-center">
                            <div class="col-12 col-md-12 col-lg-7">
                                <div class="item-infor">
                                    <div class="st-border-right">
                                        <h2 class="heading">
                                            <a href="https://staging.balkanea.com/hotel-room/$hotel_id-$url_room/?start=$check_in_date&#038;end=$check_out_date&#038;date=$check_in_date%2012:00%20am-$check_out_date%2011:59%20pm&#038;room_num_search=1&#038;adult_number=2&price=$room_price" class="heading-title">
                                                $room_name
                                            </a>
                                        </h2>
                                        <div class="facilities">
                                            <div class="st-list-facilities">
                                                <p class="item text-center" data-bs-html="true" data-bs-toggle="tooltip" data-bs-placement="top" data-toggle="tooltip" data-placement="top" title="No. Beds">
                                                    <span class="item-box"><i class="stt-icon-bed"></i></span><br/>
                                                    <span class="infor">x2</span>
                                                </p>
                                                <p class="item text-center" data-bs-html="true" data-bs-toggle="tooltip" data-bs-placement="top" data-toggle="tooltip" data-placement="top" title="No. Adults">
                                                    <span class="item-box"><i class="stt-icon-adult"></i></span><br/>
                                                    <span class="infor">x2</span>
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-12 col-md-12 col-lg-5">
                                <div class="price-wrapper">
                                    <span class="price">€$full_price</span>
                                    <span class="unit">/$nights nights</span>
                                </div>
                                <a href="https://staging.balkanea.com/hotel-room/$hotel_id-$url_room/?start=$check_in_date&#038;end=$check_out_date&#038;date=$check_in_date%2012:00%20am-$check_out_date%2011:59%20pm&#038;room_num_search=1&#038;adult_number=2&price=$room_price" target="_blank" class="show-detail btn-v2 btn-primary">Room Detail</a>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    HTML;
    
        $counter++;
    }
    
    $prev_page = $page > 1 ? $page - 1 : 1;
    $next_page = $page < $total_pages ? $page + 1 : $total_pages;

    if ($page > 1) {
        $pagination .= '<a href="#" class="pagination-link" data-page="' . $prev_page . '">&laquo; Previous</a> ';
    } else {
        $pagination .= '<a href="#" class="pagination-link disabled">&laquo; Previous</a> ';
    }

    for ($i = 1; $i <= $total_pages; $i++) {
        if ($page == $i) {
            $pagination .= '<a href="#" class="pagination-link active" data-page="' . $i . '">' . $i . '</a> ';
        } else {
            $pagination .= '<a href="#" class="pagination-link" data-page="' . $i . '">' . $i . '</a> ';
        }
    }

    if ($page < $total_pages) {
        $pagination .= '<a href="#" class="pagination-link" data-page="' . $next_page . '">Next &raquo;</a> ';
    } else {
        $pagination .= '<a href="#" class="pagination-link disabled">Next &raquo;</a> ';
    }

    echo json_encode(['html' => $output, 'pagination' => $pagination]);

    // echo $output;
}
?>
