<?php

// Load configuration settings
$config = include '../config.php';

// Function to format a date string
function format_date ($date) {
    $date = explode('T', $date)[0];
    return date('d/m/Y', strtotime($date));
}

// Set API URL and credentials
$apiUrl = 'https://api.worldota.net/api/b2b/v3/hotel/info/';
$keyId = $config['api_key'];
$apiKey = $config['api_password'];

// Initialize an array to store the response
$return_response = [];

// Initialize cURL for the API request
$ch = curl_init($apiUrl);

// Enable error reporting for debugging purposes
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Get the document root path
$path = $_SERVER['DOCUMENT_ROOT']; 

// Include WordPress core file for database access and other functionalities
include_once $path . '/wp-load.php';

// Access the global WordPress database object
global $wpdb;

// Enable WordPress database error display
$wpdb->show_errors();
$prefix = $wpdb->prefix;

// Check if order ID and service ID are provided
if (!isset($_POST['order_id']) && !isset($_POST['service_id'])) {
    echo "Error: No order ID or service ID provided";
    exit();
}

// Retrieve order item ID and hotel ID from POST parameters
$order_item_id = $_POST['order_id'];
$hotel_id = $_POST['service_id'];

// Query the database for order item meta
$query = $wpdb->prepare("SELECT * FROM " . $prefix . "st_order_item_meta WHERE order_item_id = %d AND st_booking_id = %d", $order_item_id, $hotel_id);
$results = $wpdb->get_row($query);

// Format the created date
$created_date = format_date($results->created);
$room_id = $results->room_id;

// Query the database for post meta
$query = $wpdb->prepare("SELECT * FROM " . $prefix . "postmeta WHERE post_id = %d", $order_item_id);
$results = $wpdb->get_results($query);

// Store order meta data in an array
$meta_data_order = [];
foreach ($results as $item) {
    $meta_data_order[$item->meta_key] = $item->meta_value;
}

// Query the database for room meta
$query = $wpdb->prepare("SELECT * FROM " . $prefix . "postmeta WHERE post_id = %d", $room_id);
$results = $wpdb->get_results($query);

// Store room meta data in an array
foreach ($results as $item) {
    $meta_data_room[$item->meta_key] = $item->meta_value;
}

// Retrieve address, room name, and hotel name from meta data
$address = $meta_data_room['address'];
$room_name = $meta_data_room['_yoast_wpseo_focuskw'];
$hotel_name = $meta_data_room['_wp_old_slug'];

// Extract hotel name from slug
$hotel_name = explode('-', $hotel_name)[0];

// Unserialize price information
$price_info = unserialize($meta_data_order['currency']);
$currency_and_pirce = $price_info['symbol'] . '' . $price_info['rate'];

// Unserialize cart information
$cart_info = unserialize($meta_data_order['st_cart_info']);

// Calculate total price and retrieve cart data
$price_total = $price_info['symbol'] . '' . $cart_info[$hotel_id]['price'];
$cart_info = $cart_info[$hotel_id]['data'];

// Retrieve number of adults, children, extra price, and title from cart data
$adult_number = $cart_info['adult_number'];
$child_number = $cart_info['child_number'];
$extra_price = $cart_info['extra_price'];
$title = $cart_info['title_cart'];

// Format check-in and check-out dates
$check_in =  format_date($meta_data_order['check_in']);
$check_out = format_date($meta_data_order['check_out']);
$button_cancel = '';

// Check for free cancellation availability
if (!empty($meta_data_order['free_cancelation_before'])){
    $free_cancelation_value = format_date($meta_data_order['free_cancelation_before']);
    $button_cancel = "<button style='background: #d9534f !important;' id='cancel_booking' class='btn btn-danger' type='button'>Cancel Reservation</button>";
}

// Construct HTML for modal content
$modal = <<<HTML
<div class="st_tab st_tab_order tabbable">
    <ul class="nav nav-tabs tab_order">
        <li class="active">
            <a data-toggle="tab" href="#tab-booking-detail" aria-expanded="true">Hotel Details</a>
        </li>
        <li class="">
            <a data-toggle="tab" href="#tab-customer-detail" aria-expanded="false">Customer Details</a>
        </li>
    </ul>
    <div class="tab-content" id="myTabContent973">
        <div id="tab-booking-detail" class="tab-pane fade active in">
            <div class="info">
                <div class="row">
                    <div class="col-md-6">
                        <div class="item_booking_detail">
                            <strong>Booking ID: </strong> #{$order_item_id}
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="item_booking_detail">
                            <strong>Payment Method: </strong> {$meta_data_order['payment_method_name']}
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="item_booking_detail">
                            <strong>Order Date: </strong> {$created_date}
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="item_booking_detail">
                            <strong>Booking Status: </strong>
                            <span class="">{$meta_data_order['status']}</span>
                        </div>
                    </div>
                    <div class="col-md-12">
                        <div class="item_booking_detail">
                            <strong>Hotel Name: </strong>
                            <a href="https://staging.balkanea.com/hotel/{$hotel_name}/">{$title}</a>
                        </div>
                    </div>
                    <div class="col-md-12">
                        <div class="item_booking_detail">
                            <strong>Room: </strong> {$room_name}
                        </div>
                    </div>
                    <div class="col-md-12">
                        <div class="item_booking_detail">
                            <strong>Address: </strong> {$address}
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="item_booking_detail">
                            <strong>Check In: </strong>  {$check_in}
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="item_booking_detail">
                            <strong>Check Out: </strong> {$check_out}
                        </div>
                    </div>
                    <div class="line col-md-12"></div>
                    <div class="col-md-12">
                        <div class="item_booking_detail">
                            <strong>Room Price: </strong> {$currency_and_pirce}
                        </div>
                    </div>
                    <div class="col-md-12">
                        <div class="item_booking_detail">
                            <strong>No. Adults: </strong> {$adult_number}
                        </div>
                    </div>
                    <div class="col-md-12">
                        <div class="item_booking_detail">
                            <strong>No. Children: </strong> {$child_number}
                        </div>
                    </div>
                    <div class="col-md-12">
                        <div class="item_booking_detail">
                            <strong>Free cancelation before: </strong> {$free_cancelation_value}
                        </div>
                    </div>
                    <div class="col-md-6 hide">
                        <div class="item_booking_detail">
                            <strong>Extra Price: </strong> {$extra_price}
                        </div>
                    </div>
                    <div class="line col-md-12"></div>
                    <div class="col-md-12">
                        <strong>Subtotal: </strong>
                        <div class="pull-right">
                            <strong>{$price_total}</strong>
                        </div>
                    </div>
                    <div class="col-md-12">
                        <strong>Tax: </strong>
                        <div class="pull-right">0</div>
                    </div>
                    <div class="col-md-12 hide">
                        <strong>Coupon: </strong>
                        <div class="pull-right">
                            <strong>- Free</strong>
                        </div>
                    </div>
                    <div class="col-md-12">
                        <strong>Pay Amount: </strong>
                        <div class="pull-right">
                            <strong>{$price_total}</strong>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div id="tab-customer-detail" class="tab-pane fade">
            <div class="container-customer">
                <div class="info">
                    <div class="row">
                        <div class="col-md-12">
                            <div class="item_booking_detail">
                                <strong>First name: </strong> {$meta_data_order['st_first_name']}
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="item_booking_detail">
                                <strong>Last name: </strong> {$meta_data_order['st_last_name']}
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="item_booking_detail">
                                <strong>Email: </strong> {$meta_data_order['st_email']}
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="item_booking_detail">
                                <strong>Phone: </strong> {$meta_data_order['st_phone']}
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="item_booking_detail">
                                <strong>Address Line 1: </strong> {$meta_data_order['st_phone']}
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="item_booking_detail">
                                <strong>Address Line 2: </strong> {$meta_data_order['st_address2']}
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="item_booking_detail">
                                <strong>City: </strong> {$meta_data_order['st_city']}
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="item_booking_detail">
                                <strong>State/Province/Region: </strong> {$meta_data_order['st_province']}
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="item_booking_detail">
                                <strong>ZIP code/Postal code: </strong> {$meta_data_order['st_zip_code']}
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="item_booking_detail">
                                <strong>Country: </strong> {$meta_data_order['st_country']}
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="item_booking_detail">
                                <strong>Special Requirements: </strong>
                                {$meta_data_order['st_note']}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal-footer" style="display: flex; justify-content: space-between; width: 100%;">
    {$button_cancel}
    <button id='close_modal' data-dismiss="modal" class="btn btn-default" type="button">Close</button>
</div>

HTML;

// Output the modal content
echo $modal;

?>