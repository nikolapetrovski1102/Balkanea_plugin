<?php

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

// Define booking statuses with labels and colors
$statuses = [
    "pending" => [
        "label" => __("Pending payment", "traveler"),
        "color" => "#FF9900",
    ],
    "processing" => [
        "label" => __("Processing", "traveler"),
        "color" => "#0066CC",
    ],
    "on-hold" => [
        "label" => __("On hold", "traveler"),
        "color" => "#FFCC00",
    ],
    "completed" => [
        "label" => __("Completed", "traveler"),
        "color" => "#33CC33",
    ],
    "cancelled" => [
        "label" => __("Cancelled", "traveler"),
        "color" => "#FF3333",
    ],
    "refunded" => [
        "label" => __("Refunded", "traveler"),
        "color" => "#9933FF",
    ],
    "failed" => [
        "label" => __("Failed", "traveler"),
        "color" => "#CC0000",
    ],
    "cancel-request" => [
        "label" => __("Cancel Request", "traveler"),
        "color" => "#FF6633",
    ],
    "draft" => [
        "label" => __("Draft", "traveler"),
        "color" => "#999999",
    ],
];
    
// Check if the action is to load more bookings
if (isset($_GET['action']) && $_GET['action'] == 'load_more_bookings'){
    // Get offset and current user ID from GET parameters
    $offset = isset($_GET['offset']) ? intval($_GET['offset']) : 0;
    $current_user_id = isset($_GET['current_user']) ? intval($_GET['current_user']) : 0;

    // Query the database for bookings
    $bookings = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT so.*, pp.*, 
            MAX(CASE WHEN ps.meta_key = '_order_total' THEN ps.meta_value END) AS order_total,
            MAX(CASE WHEN ps.meta_key = '_billing_country' THEN ps.meta_value END) AS billing_country,
            MAX(CASE WHEN ps.meta_key = 'free_cancellation' THEN ps.meta_value END) AS free_cancellation
            FROM {$wpdb->prefix}st_order_item_meta AS so
            LEFT JOIN {$wpdb->prefix}posts AS pp
            ON so.room_id = pp.ID
            LEFT JOIN {$wpdb->prefix}postmeta AS ps
            ON so.wc_order_id = ps.post_id
            WHERE so.user_id = %d AND so.type = 'woocommerce'
            GROUP BY so.wc_order_id
            ORDER BY so.wc_order_id DESC
            LIMIT 15 OFFSET %d",
            $current_user_id,
            $offset
        )
    );
    
    // Check if bookings are found
    if (!empty($bookings)) {
        ob_start(); // Start output buffering
        foreach ($bookings as $booking) {
            ?>
            <tr>
                <td><?php echo esc_html($booking->wc_order_id); ?></td>
                <td><a href="<?php echo esc_url("{$room_url}/{$booking->post_name}"); ?>" target='_blank' ><?php echo esc_html($booking->post_title); ?></a></td>
                <td><?php echo esc_html(strip_tags(wc_price($booking->total_order))); ?></td>
                <td>
                    <?php 
                        // Get booking status and display it with color
                        $status_key = explode('-', $booking->status)[1] ?? null;
                        $status_data = $statuses[$status_key] ?? null;
                        
                        if ($status_data) {
                            echo '<span style="color: ' . esc_attr($status_data['color']) . ';">' . esc_html($status_data['label']) . '</span>';
                        } else {
                            echo esc_html(__('Unknown', 'traveler'));
                        }
                    ?>
                </td>
                <td><?php echo esc_html($booking->created); ?></td>
                <td>
                <?php
                    // Added mapping for free_cancellation 26.04.2025
                    if (empty($booking->free_cancellation)) {
                        echo esc_html("N/A");
                    } else {
                        $free_cancellation_date = DateTime::createFromFormat('d/m/Y', $booking->free_cancellation);
                        $now = new DateTime();
                
                        if ($free_cancellation_date && $free_cancellation_date < $now) {
                            echo esc_html("Free cancellation period is over");
                        } else {
                            echo esc_html($booking->free_cancellation);
                        }
                    }
                ?>
                </td>
                <td>
                    <button 
                        onclick="ViewModal(this)"
                        class="btn btn-info view-booking-details" 
                        data-toggle="modal"
                        data-target="#bookingDetailsModal"
                        data-id="<?php echo esc_attr($booking->wc_order_id); ?>"
                        data-product="<?php echo esc_html($booking->post_title); ?>"
                        data-cost="<?php echo esc_html(strip_tags(wc_price($booking->total_order))); ?>"
                        data-status="<?php echo esc_html($statuses[$status_key]['label'] ?? __('Unknown', 'traveler')); ?>"
                        data-date="<?php echo esc_html($booking->created); ?>"
                    >
                        <?php echo __('View', 'traveler'); ?>
                    </button>
                </td>
            </tr>
            <?php
        }
        $html = ob_get_clean(); // Get buffered content
        wp_send_json_success($html); // Send success response with HTML content
    } else {
        wp_send_json_error(); // Send error response if no bookings found
    }
}
// Check if the action is to load modal details
else if (isset($_GET['action']) && $_GET['action'] == 'load_modal'){

        // Get order ID and current user ID from GET parameters
        $order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;
        $current_user_id = isset($_GET['current_user']) ? intval($_GET['current_user']) : 0;
        
        // Query the database for order item meta and post meta
        $query = "SELECT * FROM {$wpdb->prefix}st_order_item_meta AS st LEFT JOIN {$wpdb->prefix}postmeta AS pp ON st.wc_order_id = pp.post_id WHERE st.user_id = {$current_user_id} AND st.wc_order_id = {$order_id};";
    
        $postmeta_values = [];
    
        $base_booking_info = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}st_order_item_meta WHERE user_id = %d AND wc_order_id = %d;", $current_user_id, $order_id));
        $advance_booking_info = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}postmeta WHERE post_id = %d;", $order_id));
    
        // Store post meta values in an array
        foreach ($advance_booking_info as $meta) {
            $postmeta_values[$meta->meta_key] = $meta->meta_value;
        }
        
        // Check if base booking info is found
        if (!empty($base_booking_info)) {
            $hotel_details_content = '';
            ob_start(); // Start output buffering
            $booking = $base_booking_info[0];
            $raw_data = json_decode($booking->raw_data);
            
            // Query the database for hotel and room information
            $hotel_info_array = $wpdb->get_results($wpdb->prepare("SELECT hotel.ID AS hotel_id, hotel.post_title AS hotel_name, pm_address.meta_value AS hotel_address, CONCAT('" . site_url() . "/hotel/', hotel.post_name) AS hotel_url, room.post_title AS room_type " . 
                "FROM {$wpdb->prefix}posts AS room LEFT JOIN {$wpdb->prefix}postmeta AS pm_parent ON room.ID = pm_parent.post_id AND pm_parent.meta_key = 'room_parent' " . 
                "LEFT JOIN {$wpdb->prefix}posts AS hotel ON pm_parent.meta_value = hotel.ID LEFT JOIN {$wpdb->prefix}postmeta AS pm_address ON hotel.ID = pm_address.post_id AND pm_address.meta_key = 'address' " . 
                "WHERE room.ID = " . $booking->room_id . " AND hotel.post_status = 'publish'"));
            $hotel_info = $hotel_info_array[0];
            // Nikola BAL-632 START
            $free_cancellation_button = '';
            $datetime_now = new DateTime('now', new DateTimeZone('UTC'));
            $free_cancellation_text = 'No free cancellation';
            $free_cancellation_button = '<button class="btn btn-secondary" data-dismiss="modal" type="button" disabled> Free Cancellation Not Available </button>';
            
            if (isset($postmeta_values['free_cancellation'])) {
                $free_cancellation_raw = $postmeta_values['free_cancellation'] ?? null;
            
                if (in_array($booking->status, ['wc-completed', 'wc-cancelled', 'wc-failed'])) {
                    
                    if ($booking->status == 'wc-completed') {
                        $free_cancellation_text = 'Order is completed. Free cancellation is no longer available.';
                    } elseif ($booking->status == 'wc-cancelled') {
                        $free_cancellation_text = 'Order is already cancelled.';
                    } elseif ($booking->status == 'wc-failed') {
                        $free_cancellation_text = 'Order has failed. Free cancellation is not possible.';
                    }
                    
                    $free_cancellation_button = '<button class="btn btn-secondary" data-dismiss="modal" type="button" disabled> Free Cancellation Not Available </button>';
            
                } elseif ($booking->status == 'wc-processing') {
                    if ($free_cancellation_raw) {
                        $free_cancellation_date = DateTime::createFromFormat('d/m/Y', $free_cancellation_raw);
            
                        if ($free_cancellation_date) {
                            $free_cancellation_text = 'Free cancellation available until ' . $free_cancellation_date->format('d/m/Y');
            
                            if ($free_cancellation_date > $datetime_now) {
                                // Free cancellation is still possible
                                $free_cancellation_button = "<button data-order-id='$order_id' id='close_modal' data-dismiss='modal' onclick='CancelOrder(this)' class='btn btn-danger' type='button'> Cancel Order </button>";
                            } else {
                                // Date passed
                                $free_cancellation_text = 'Free cancellation period has expired.';
                                $free_cancellation_button = '<button class="btn btn-secondary" data-dismiss="modal" type="button" disabled> Free Cancellation Not Available </button>';
                            }
                        } else {
                            error_log("Error parsing free_cancellation_raw for order ID: $order_id.");
                        }
                    }
                }
            }
            // Nikola BAL-632 END


        
        ?>
        <div class="modal-content-inner">
            <div class="st_tab st_tab_order tabbable">
                <ul class="nav nav-tabs tab_order">
                    <li class="active">
                        <a data-toggle="tab" href="#tab-booking-detail" aria-expanded="true">Hotel Details</a>
                    </li>
                    <li>
                        <a data-toggle="tab" href="#tab-customer-detail" aria-expanded="false">Customer Details</a>
                    </li>
                </ul>
                <div class="tab-content" id="myTabContent973">
                    <div id="tab-booking-detail" class="tab-pane fade active in">
                        <div class="info">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="item_booking_detail">
                                        <strong>Booking ID: </strong> #<?php echo esc_html($booking->wc_order_id); ?>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="item_booking_detail">
                                        <strong>Payment Method: </strong> <?php echo esc_html($postmeta_values['_payment_method_title'] ?? 'No payment provided'); ?> <!-- Populate payment method here -->
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="item_booking_detail">
                                        <strong>Order Date: </strong> <?php echo date('d/m/Y', strtotime($booking->created)); ?>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="item_booking_detail">
                                        <strong>Booking Status: </strong>                     
                                        <?php 
                                        $status_key = explode('-', $booking->status)[1] ?? null;
                                        $status_data = $statuses[$status_key] ?? null;
                        
                                        if ($status_data) {
                                            echo '<span style="color: ' . esc_attr($status_data['color']) . ';">' . esc_html($status_data['label']) . '</span>';
                                        } else {
                                            echo esc_html(__('Unknown', 'traveler'));
                                        }
                                    ?>
                                    </div>
                                </div>
                                <div class="col-md-12">
                                    <div class="item_booking_detail">
                                        <strong>Hotel Name: </strong>
                                        <a href="<?php echo esc_url($hotel_info->hotel_url); ?>" target="_blank"><?php echo esc_html($hotel_info->hotel_name); ?></a>
                                    </div>
                                </div>
                                <div class="col-md-12">
                                    <div class="item_booking_detail">
                                        <strong>Room: </strong> <?php echo esc_html($hotel_info->room_type); ?>
                                    </div>
                                </div>
                                <div class="col-md-12">
                                    <div class="item_booking_detail">
                                        <strong>Address: </strong> <?php echo esc_html($hotel_info->hotel_address); ?>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="item_booking_detail">
                                        <strong>Check-In:</strong> <?php echo DateTime::createFromFormat('d-m-Y', $booking->check_in)->format('d/m/Y'); ?>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="item_booking_detail">
                                        <strong>Check-Out:</strong> <?php echo DateTime::createFromFormat('d-m-Y', $booking->check_out)->format('d/m/Y'); ?>
                                    </div>
                                </div>
                                <div class="line col-md-12"></div>
                                <div class="col-md-12">
                                    <div class="item_booking_detail">
                                        <strong>Room Price: </strong><?php echo $postmeta_values['_order_currency'] . ' ' . number_format((float)$postmeta_values['_order_total'], 2); ?>
                                    </div>
                                </div>
                                <div class="col-md-12">
                                    <div class="item_booking_detail">
                                        <strong>No. Adults: </strong> <?php echo esc_html($booking->adult_number); ?>
                                    </div>
                                </div>
                                <div class="col-md-12">
                                    <div class="item_booking_detail">
                                        <strong>No. Children: </strong> <?php echo esc_html($booking->child_number); ?>
                                    </div>
                                </div>
                                <div class="col-md-12">
                                    <div class="item_booking_detail">
                                        <strong>Free cancellation before: </strong> <?php echo $free_cancellation_text; ?>
                                    </div>
                                </div>
                                <div class="col-md-6 hide">
                                    <div class="item_booking_detail">
                                        <strong>Extra Price: </strong> $<?php echo number_format((float)$booking->extra_price, 2); ?>
                                    </div>
                                </div>
                                <div class="line col-md-12"></div>
                                <div class="col-md-12 hide">
                                    <strong>Subtotal: </strong>
                                    <div class="pull-right">
                                        <strong>$<?php echo number_format((float)$raw_data->total_price_origin, 2); ?></strong>
                                    </div>
                                </div>
                                <div class="col-md-12 hide">
                                    <strong>Tax: </strong>
                                    <div class="pull-right"><?php echo number_format((float)$booking->tax, 2); ?></div>
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
                                        <strong>$<?php echo number_format((float)$raw_data->total_price, 2); ?></strong>
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
                                            <strong>First name: </strong> <?php echo esc_html($postmeta_values["_billing_first_name"]); ?>
                                        </div>
                                    </div>
                                    <div class="col-md-12">
                                        <div class="item_booking_detail">
                                            <strong>Last name: </strong> <?php echo esc_html($postmeta_values["_billing_last_name"]); ?>
                                        </div>
                                    </div>
                                    <div class="col-md-12">
                                        <div class="item_booking_detail">
                                            <strong>Email: </strong> <?php echo esc_html($postmeta_values["_billing_email"]); ?>
                                        </div>
                                    </div>
                                    <div class="col-md-12">
                                        <div class="item_booking_detail">
                                            <strong>Phone: </strong> <?php echo esc_html($postmeta_values["_billing_phone"]); ?>
                                        </div>
                                    </div>
                                    <div class="col-md-12">
                                        <div class="item_booking_detail">
                                            <strong>Address Line 1: </strong> <?php echo esc_html($postmeta_values["_billing_address_1"]); ?>
                                        </div>
                                    </div>
                                    <div class="col-md-12">
                                        <div class="item_booking_detail">
                                            <strong>Address Line 2: </strong> <?php echo esc_html($postmeta_values["_billing_address_2"]); ?>
                                        </div>
                                    </div>
                                    <div class="col-md-12">
                                        <div class="item_booking_detail">
                                            <strong>City: </strong> <?php echo esc_html($postmeta_values["_billing_city"]); ?>
                                        </div>
                                    </div>
                                    <div class="col-md-12">
                                        <div class="item_booking_detail">
                                            <strong>State/Province/Region: </strong> <?php echo esc_html($postmeta_values["_billing_state"]); ?>
                                        </div>
                                    </div>
                                    <div class="col-md-12">
                                        <div class="item_booking_detail">
                                            <strong>ZIP code/Postal code: </strong> <?php echo esc_html($postmeta_values["_billing_postcode"]); ?>
                                        </div>
                                    </div>
                                    <div class="col-md-12">
                                        <div class="item_booking_detail">
                                            <strong>Country: </strong> <?php echo esc_html($postmeta_values["_billing_country"]); ?>
                                        </div>
                                    </div>
                                    <div class="col-md-12 hide">
                                        <div class="item_booking_detail">
                                            <strong>Special Requirements: </strong> <?php echo esc_html($booking->special_requirements); ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer" style="display: flex; justify-content: space-between; width: 100%;">
                <button id="close_modal" data-dismiss="modal" class="btn btn-default" type="button">Close</button>
                <?php echo $free_cancellation_button ?>
            </div>
        </div>
        <?php
        
        $hotel_details_content = ob_get_clean();
    
        wp_send_json_success(['data' => $hotel_details_content]); // Send success response with modal content
    } else {
        echo json_encode(['success' => false, 'data' => "No booking details found with query: {$query}"]);
    }
}
?>