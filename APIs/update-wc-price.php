<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if WooCommerce is loaded

// Example usage of `wc_add_order_item_meta` for 'printJobId'
// wc_add_order_item_meta($item_id, 'printJobId', $values['printJobId']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Sanitize and validate input
    if (!function_exists('WC')) {
        error_log(json_encode(['status' => 'error', 'message' => 'WooCommerce is not loaded']));
        exit;
    }

    $new_price = isset($_POST['full_price']) ? floatval($_POST['full_price']) : 0.0;

    // Ensure the new price is valid
    if ($new_price <= 0) {
        error_log(json_encode(['status' => 'error', 'message' => 'Invalid price']));
        exit;
    }

    // Get the WooCommerce cart instance
    $cart = WC()->cart;

    if (!$cart) {
        error_log(json_encode(['status' => 'error', 'message' => 'Cart is not available']));
        exit;
    }

    // Iterate through cart items to update price and add metadata
    foreach ($cart->get_cart() as $cart_item_key => $cart_item) {
        // Update the price of the cart item
        $cart_item['data']->set_price($new_price);

        // Add custom metadata for the price
        wc_add_order_item_meta($cart_item_key, 'Custom Price', $new_price);

        // Recalculate cart totals
        $cart->calculate_totals();

        break; // Exit loop after updating the first item
    }
} else {
    error_log(json_encode(['status' => 'error', 'message' => 'Invalid request method']));
}
