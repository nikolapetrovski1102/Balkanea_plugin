<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$path = $_SERVER['DOCUMENT_ROOT']; 

include_once $path . '/wp-load.php';
session_start();

global $wpdb;

$wpdb->show_errors();
$prefix = $wpdb->prefix;

if (isset($_GET['post_name'])){

    $post_name = $_GET['post_name'] ?? "";

    $query = $wpdb->prepare(
        "SELECT * FROM `{$prefix}posts` WHERE post_name = '{$post_name}' AND post_author = 6961"
    );

    $results = $wpdb->get_results($query);
    
    if ($results)
        echo 'true';
    else
        echo 'false';
    
}
else {
    echo json_encode(array("error" => "Bad Request"), true);
}
?>