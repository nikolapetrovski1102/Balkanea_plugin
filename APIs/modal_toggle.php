<?php

$apiUrl = 'https://api.worldota.net/api/b2b/v3/hotel/info/';
$keyId = '7788';
$apiKey = 'e6a79dc0-c452-48e0-828d-d37614165e39';

$return_response = [];

$ch = curl_init($apiUrl);

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$path = $_SERVER['DOCUMENT_ROOT']; 

include_once $path . '/wp-load.php';

global $wpdb;

$wpdb->show_errors();
$prefix = $wpdb->prefix;

if (!isset($_POST['order_id']) && !isset($_POST['service_id'])) {
    echo "Error: No order ID or service ID provided";
    exit();
}

    $order_item_id = $_POST['order_id'];
    $hotel_id = $_POST['service_id'];

    $query = $wpdb->prepare("SELECT * FROM " . $prefix . "st_order_item_meta WHERE order_item_id = %d AND st_booking_id = %d", $order_item_id, $hotel_id);
    $results = $wpdb->get_row($query);

    
    // $item_price = $response->item_price;
    // $ori_price = $response->ori_price;
    // $sale_price = $response->sale_price;
    // $check_in = $response->check_in;
    // $check_out = $response->check_out;
    // $cancelation_date = $response->cancelation_date;
    // $room_num_search = $response->room_num_search;
    // $room_id = $response->room_id;
    // $adult_number = $response->adult_number;
    // $child_number = $response->child_number;
    // $extras = $response->extras; // Assuming it's an array
    // $extra_price = $response->extra_price;
    // $extra_type = $response->extra_type;
    // $commission = $response->commission;
    // $discount_rate = $response->discount_rate;
    // $guest_title = $response->guest_title[0]; // Assuming the first title
    // $guest_name = $response->guest_name[0]; // Assuming the first guest name
    // $total_price_origin = $response->total_price_origin;
    // $total_bulk_discount = $response->total_bulk_discount;
    // $st_booking_post_type = $response->st_booking_post_type;
    // $st_booking_id = $response->st_booking_id;
    // $sharing = $response->sharing;
    // $duration_unit = $response->duration_unit;
    // $title_cart = $response->title_cart;
    // $deposit_money_type = $response->deposit_money->type;
    // $deposit_money_amount = $response->deposit_money->amount;
    // $user_id = $response->user_id;
    
    $created_date = $results->created;
    $room_id = $results->room_id;
    
    $query = $wpdb->prepare("SELECT * FROM " . $prefix . "postmeta WHERE post_id = %d", $order_item_id);
    $results = $wpdb->get_results($query);

    $meta_data_order = [];
    
    foreach ($results as $item) {
        $meta_data_order[$item->meta_key] = $item->meta_value;
    }

    $query = $wpdb->prepare("SELECT * FROM " . $prefix . "postmeta WHERE post_id = %d", $room_id);
    $results = $wpdb->get_results($query);

    foreach ($results as $item) {
        $meta_data_room[$item->meta_key] = $item->meta_value;
    }

    $address = $meta_data_room['address'];
    $room_name = $meta_data_room['_yoast_wpseo_focuskw'];
    $hotel_name = $meta_data_room['_wp_old_slug'];

    $hotel_name = explode('-', $hotel_name)[0];

    $price_info = unserialize($meta_data_order['currency']);
    $currency_and_pirce = $price_info['symbol'] . '' . $price_info['rate'];

    $cart_info = unserialize($meta_data_order['st_cart_info']);

    $price_total = $price_info['symbol'] . '' . $cart_info[$hotel_id]['price'];
    $cart_info = $cart_info[$hotel_id]['data'];

    $adult_number = $cart_info['adult_number'];
    $child_number = $cart_info['child_number'];
    $extra_price = $cart_info['extra_price'];
    $title = $cart_info['title_cart'];

    $body_data = array(
        "id" => $hotel_name,
        "language" => 'en'
    );

    $data = json_encode($body_data);

    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Basic ' . base64_encode("$keyId:$apiKey")
    ]);

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);

    $response = curl_exec($ch);

    $check_in = explode(' ', $meta_data_order['check_in'])[0];
    $check_out = explode(' ', $meta_data_order['check_out'])[0];

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
                            <!-- <div class="col-md-12">
                                <div class="item_booking_detail">
                                    <strong>Room Number: </strong> 1
                                </div>
                            </div> -->
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


        <div class="modal-footer">
            <a href='' data-dismiss="modal" class="btn btn-danger" type="button">Cancel</a>
            <button data-dismiss="modal" class="btn btn-default" type="button">Close</button>
        </div>
        HTML;

    echo $modal;

?>