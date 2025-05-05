<?php

// Enable error reporting for debugging purposes
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Get the document root path
$path = $_SERVER['DOCUMENT_ROOT']; 

// Include WordPress core file for database access and other functionalities
include_once $path . '/wp-load.php';

// Start a new session or resume the existing session
session_start();

// Access the global WordPress database object
global $wpdb;

// Security check: Verify the nonce to ensure the request is valid
if (!isset($_GET['security']) || !wp_verify_nonce($_GET['security'], 'search_room')) {
    http_response_code(403);
    echo json_encode(['error' => 'Invalid nonce: ' . $_GET['security']]);
    exit;
}

// Load configuration settings
$config = include '../config.php';

// Set API URL and credentials
$apiUrl = 'https://api.worldota.net/api/b2b/v3/search/hp/';
$keyId = $config['api_key'];
$apiKey = $config['api_password'];
$query_url = 'https://staging.balkanea.com/wp-plugin/APIs/request_query.php';

// Function to make HTTP GET requests using cURL
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

// Function to format a date string
function format_date ($date) {
    return date('d/m/Y', strtotime($date));
}

// Function to convert currency code to symbol
function currency_coverter ($current_currency){
    switch ($current_currency) {
        case 'USD':
            return 'US$';
        case 'EUR':
            return '€';
        case 'MKD':
            return 'MKD';
        default:
            return '€';
    }
}

// Function to check if an image URL returns a 200 HTTP status
function imageExists($url) {
    $headers = @get_headers($url);
    return $headers && strpos($headers[0], '200') !== false;
}

// Check if required GET parameters are set
if (isset($_GET['currency']) && isset($_GET['checkin']) && isset($_GET['checkout']) && isset($_GET['guests']) && isset($_GET['hotel_id'])) {
    // Retrieve and process GET parameters
    $checkin = $_GET['checkin'];
    $checkout = $_GET['checkout'];
    $guests = json_decode(stripslashes($_GET['guests']), true);
    $hotel_id = $_GET['hotel_id'];
    $page = isset($_GET['page']) ? intval($_GET['page']) : 1;
    $guests_adults = intval($guests[0]['adults']);
    $guests_children[] = intval($guests[0]['children'][0]);
    $current_currency = $_GET['currency'];
    $current_currency_symbol = currency_coverter( $_GET['currency'] );
}

// Get the user's nationality using a shortcode
$nationality = do_shortcode('[userip_location type="countrycode"]') ?? "MK";

// Initialize output variable
$output = '';
$counter = 0;

// Prepare the request body for the API call
$body_data = array(
    "checkin" => $checkin,
    "checkout" => $checkout,
    "residency" => strtolower($nationality),
    "language" => "en",
    "guests" => array(
        array(
            "adults" => $guests_adults,
            "children" => $guests_children[0] == 0 ? array() : $guests_children
        )
    ),
    "id" => trim($hotel_id, '"'),
    "currency" => $current_currency,
);

// Encode the request body as JSON
$data = json_encode($body_data);

// Initialize cURL for the API request
$ch = curl_init($apiUrl);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Authorization: Basic ' . base64_encode("$keyId:$apiKey")
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $data);

// Execute the API request and close the cURL session
$response = curl_exec($ch);
curl_close($ch);

// Check for cURL errors
if ($response === false) {
    echo 'Curl error: ' . curl_error($ch);
} else {
    try{
    // Decode the API response
    $response = json_decode($response, true);

    // Retrieve the hotel ID from the response
    $hotel_id = $response['data']['hotels'][0]['id'];

    // Check for validation errors in the response
    if ($response['debug']['validation_error'] != null){
        echo $response['debug']['validation_error'];
        error_log($response['debug']['validation_error']);
        exit();
    }

    // Check if there are no rooms available
    if (count($response['data']['hotels']) == 0){
        echo 'No rooms';
        error_log("No rooms");
        exit();
    }

    // Set pagination variables
    $items_per_page = 5;
    $start_index = ($page - 1) * $items_per_page;
    $end_index = $start_index + $items_per_page;
    $total_rates = count($response['data']['hotels'][0]['rates']);
    $total_pages = $current_currency == 'MKD' ? ceil($total_rates / $items_per_page) : $total_rates / $items_per_page;

    // Iterate over the room rates in the response
    foreach ($response['data']['hotels'][0]['rates'] as $index => $room) {
            // Skip rooms before the start index
            if ($index < $start_index) {
                continue;
            }
    
            // Stop if the maximum number of items per page is reached
            if ($counter >= $items_per_page) {
                break;
            }
    
            // Retrieve room details
            $book_hash = $room['book_hash'];
            $meal = str_replace('-', ' ', $room['meal'] ?? '');
            $room_name = $room['room_name'];
            $room_main_name = $room['room_data_trans']['main_name'];
            $room_price_no_commission = $room['payment_options']['payment_types'][0]['show_amount'];
            $room_price = $current_currency == "MKD" ? round( $room_price_no_commission * 1.10 ) : ($room_price_no_commission * 1.10);
            $url_hotel_id = urlencode($hotel_id);
            $url_room_name = urlencode($room_main_name);
            $url_room = str_replace(' ', '-', $room_main_name);
            
            // Format check-in and check-out dates
            $check_in_date = format_date($checkin);
            $check_out_date = format_date($checkout);
            $nights = (new DateTime($checkout))->diff(new DateTime($checkin))->days;
            $daily_room_price = $current_currency == "MKD" ? $room_price / intval($nights) : ($room_price / intval($nights));
    
            // Construct image URL and path
            $image_url = "https://staging.balkanea.com/wp-content/uploads/RateHawk/$url_hotel_id/$url_room/{$url_room}-1.jpg";
            $image_path = "{$_SERVER['DOCUMENT_ROOT']}/wp-content/uploads/RateHawk/$url_hotel_id/$url_room/{$url_room}-1.jpg";
    
            // Skip if the image does not exist
            if (!file_exists($image_path)) {
                continue;
            }
    
            // Retrieve cancellation penalties
            $cancellation_penalties = $room['payment_options']['payment_types'][0]['cancellation_penalties'] ?? null;
            
            // Format meal type
            $meal_type = $meal != null ? "<p style='margin-top: 10%;' class='meal-type'> <strong>{$meal}</strong> included </p>" : "";
            
            // Calculate taxes and fees
            $tax_data = $room['payment_options']['payment_types'][0]['tax_data']['taxes'] ?? [];
            $total_taxes_and_fees = 0;
            $fees = [];
            
            foreach ($tax_data as $tax) {
                if ($tax['included_by_supplier'] == false) {
                    $tax_currency = $tax['currency_code'];
                    $total_taxes_and_fees += (float)$tax['amount'];
                    
                    $fee_total = $total_taxes_and_fees . ' ' . $tax_currency;
                    
                    $fees[$tax['name']] = $fee_total;
                }
            }
            
            $total_taxes_and_fees = $tax_currency . ' ' . $total_taxes_and_fees;
            
            $fees = json_encode($fees);
    
            // Determine free cancellation policy
            $free_cancellation_before = $cancellation_penalties['free_cancellation_before'] ?? null;
            
            if ($free_cancellation_before == null) {
                $free_cancellation_before_text = null;
            } else {
                $adjusted_date = date('Y-m-d', strtotime('-1 day', strtotime(explode('T', $free_cancellation_before)[0])));
                $free_cancellation_before_formated = format_date($adjusted_date);
                $free_cancellation_before_text = "<span class='badge bg-success'>
                                                        Free Cancellation before $free_cancellation_before_formated
                                                    </span>";
            }
    
            // Retrieve room extension data
            $rg_ext = $room['rg_ext'];
            $room_data_trans = $room['room_data_trans'];
            $bathroom_type = $room_data_trans['bathroom'];
            $bathrooms = $rg_ext['bathroom'] == 0 ? 1 : $rg_ext['bathroom'];
            $bedrooms = $rg_ext['bedrooms'] == 0 ? $rg_ext['bedding'] : $rg_ext['bedrooms'];
            $capacity = $rg_ext['capacity'] == 0 ? $rg_ext['bathroom'] : $rg_ext['capacity'];
            
            // Construct HTML for room facilities
            $facilities = <<<HTML
    <p class="item text-center" data-bs-html="true" data-bs-toggle="tooltip" data-bs-placement="top" data-toggle="tooltip" data-placement="top" title="" data-bs-original-title="No. Bathrooms">
        <span class="item-box"><i class="fas fa-bath"></i></span><br/>
        <span class="infor">x{$bathrooms}</span>
    </p>
    <p class="item text-center" data-bs-html="true" data-bs-toggle="tooltip" data-bs-placement="top" data-toggle="tooltip" data-placement="top" title="" data-bs-original-title="No. Bedrooms">
        <span class="item-box"><i class="fas fa-bed"></i></span><br/>
        <span class="infor">x{$bedrooms}</span>
    </p>
    <p class="item text-center" data-bs-html="true" data-bs-toggle="tooltip" data-bs-placement="top" data-toggle="tooltip" data-placement="top" title="" data-bs-original-title="Capacity">
        <span class="item-box"><i class="fas fa-user-alt"></i></span><br/>
        <span class="infor">x{$capacity}</span>
    </p>
    HTML;
    
        // Append room details to the output
        $output .= <<<HTML
<div class="item st-border-radius">
    <form class="form-booking-inpage" method="post">
        <input type="hidden" name="check_in" value="$check_in_date"/>
        <input type="hidden" name="check_out" value="$check_out_date"/>
        <input type="hidden" name="room_num_search" value="1"/>
        <input type="hidden" name="adult_number" value="$guests_adults"/>
        <input type="hidden" name="free_cancellation_before" value="$free_cancellation_before_formated"/>
        <input type="hidden" name="child_number" value="$guests_children[0]"/>
        <input name="action" value="hotel_add_to_cart" type="hidden">
        <input type="hidden" name="start" value="$check_in_date"/>
        <input type="hidden" name="end" value="$check_out_date"/>
        <input type="hidden" name="is_search_room" value="1">
        <input type="hidden" name="full_price" value="$room_price">
        <input type="hidden" name="daily_room_price" value="$daily_room_price">
        <input type="hidden" name="fees" value='$fees'>
        <input type="hidden" name="url_room" value='$url_room'>
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
                                <h2 style="margin-bottom: 15px;" class="heading">
                                    <a href="#" class="heading-title handle-wc-price-update">
                                        $room_name
                                    </a>
                                    {$meal_type}
                                </h2>
                                <div class="facilities">
                                    <div class="st-list-facilities">
                                        $facilities
                                    </div>
                                </div>
                                <div class="free-cancellation">
                                    $free_cancellation_before_text
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-md-12 col-lg-5">
                    <div class="price-wrapper">
                        <span class="price">{$current_currency_symbol} {$room_price}</span>
                        <span class="unit">/$nights nights</span>
                        <div class="tax-details">
                            + {$total_taxes_and_fees} taxes and fees
                        </div>
                    </div>
                    <div>
                        <a href="#" class="show-detail btn-v2 btn-primary handle-wc-price-update">
                            Room Detail <i id="room-spinner" class="d-none fas fa-spinner fa-spin"></i>
                        </a>
                    </div>
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
    
    $output .= <<<HTML
<script>
    jQuery(document).ready(function ($) {
        $('.handle-wc-price-update').on('click', function (e) {
        
            const button = $(this);
            const spinner = button.find('#room-spinner');
    
            spinner.removeClass('d-none');

            e.preventDefault();
            
            const form = $(this).closest('.form-booking-inpage');
            
            let formData = form.serializeArray();
            
            formData.push(
                { name: 'security', value: st_params._s },
                { name: 'action', value: 'update_cart_price' }
            );
            
            let formattedData = {};
            formData.forEach(item => {
                formattedData[item.name] = item.value;
            });
            
            var url_room = formattedData['url_room']
            
            $.ajax({
                url: st_params.ajax_url,
                type: 'POST',
                data: formattedData,
                success: function (response) {
                    if (response.success === true) {
                        window.location.href = "https://staging.balkanea.com/hotel-room/$hotel_id-" + url_room + "/?start=$check_in_date&end=$check_out_date&date=$check_in_date%2012:00%20am-$check_out_date%2011:59%20pm&room_num_search=1&adult_number=$guests_adults&child_number=$guests_children[0]&price={$current_currency} {$full_price}&book_hash=$book_hash&free_cancellation_before=$free_cancellation_before_formated"
                    }
                },
                error: function (xhr, status, error) {
                    console.error('AJAX Error:');
                    console.error(xhr);
                    console.error(status);
                    console.error(error);
                },
            });
        });
    });
</script>
HTML;

}catch(\Exception $ex){
        error_log($e->getMessage());
        echo 'No rooms';
        exit();
    }

    echo json_encode(['html' => $output, 'pagination' => $pagination]);
}
?>
