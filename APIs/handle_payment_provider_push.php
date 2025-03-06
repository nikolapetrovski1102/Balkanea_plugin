<?php

function handle_payment_provider_push(WP_REST_Request $request) {
    $json_data = $request->get_json_params();
    error_log("Push message received: " . print_r($json_data, true));

    // Extract data from JSON payload
    $order_id = $json_data['order_id'] ?? null;
    $status = $json_data['status'] ?? null;
    $reason_of_decline = $json_data['ReasonOfDecline'] ?? null;
    $three_d_secure = $json_data['3DSecure'] ?? null;
    $card_number = $json_data['CardNumber'] ?? null;

    // Validate the order ID
    if (!$order_id) {
        return new WP_REST_Response('Missing order ID', 400);
    }

    // Get WooCommerce order
    $order = wc_get_order($order_id);
    if (!$order) {
        return new WP_REST_Response('Invalid order ID', 404);
    }

    // Update order status and save metadata based on response
    if ($status === 'success') {
        $order->update_status('completed');
        $order->add_order_note("Payment successful. Card: {$card_number}");
    } elseif ($status === 'failed') {
        $order->update_status('failed');

        $order->add_order_note("Payment failed. Reason: {$decline_reason}");
    }

    // Add additional metadata to order
    update_post_meta($order_id, '_3DSecure_status', $three_d_secure);
    update_post_meta($order_id, '_card_number_masked', $card_number);

    return new WP_REST_Response('Order updated', 200);
}


?>