<?php
require_once($_SERVER['DOCUMENT_ROOT'] . '/wp-load.php');

header('Content-Type: application/json'); // Ensure JSON response
ob_start(); // Start output buffering

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (!class_exists('WooCommerce')) {
    ob_clean();
    die(json_encode(['success' => false, 'message' => 'WooCommerce is not active.']));
}

$raw_input = file_get_contents('php://input');
$data = json_decode($raw_input, true);

if (!isset($data['order_id']) || empty($data['order_id'])) {
    ob_clean();
    die(json_encode(['success' => false, 'message' => 'Order ID is required.']));
}

$order_id = intval($data['order_id']);

try {
    $order = wc_get_order($order_id);

    if (!$order) {
        ob_clean();
        die(json_encode(['success' => false, 'message' => "Order with ID {$order_id} not found."]));
    }

    if ($order->get_status() === 'cancelled') {
        ob_clean();
        die(json_encode(['success' => false, 'message' => "Order ID {$order_id} is already cancelled."]));
    }

    if (in_array($order->get_status(), ['processing', 'completed'])) {
        $order->update_status('cancelled', 'Order was cancelled programmatically.');
    }

    ob_clean();
    echo json_encode(['success' => true, 'data' => 'ok']);
    exit;
} catch (Exception $e) {
    ob_clean();
    die(json_encode(['success' => false, 'message' => 'Failed to cancel order: ' . $e->getMessage()]));
}
?>
