<?php

// Load WordPress
$path = $_SERVER['DOCUMENT_ROOT'];
include_once $path . '/wp-load.php';

// Set response headers
header('Content-Type: application/json');

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['error' => 'Invalid request method']);
    exit;
}

// Read raw POST data
$rawInput = file_get_contents("php://input");

// Debug: Check if raw input is received
if (empty($rawInput)) {
    echo json_encode(['error' => 'No JSON received']);
    exit;
}

// Decode JSON input
$input = json_decode($rawInput, true);

// Debug: Check if JSON decoding worked
if ($input === null) {
    echo json_encode(['error' => 'Invalid JSON format', 'raw_input' => $rawInput]);
    exit;
}

// Check if 'action' parameter exists
if (!isset($input['action']) || empty($input['action'])) {
    echo json_encode(['error' => 'Missing action parameter']);
    exit;
}

// Generate nonce
$nonce = wp_create_nonce($input['action']);

// Return nonce
echo json_encode(['nonce' => $nonce]);
exit;
