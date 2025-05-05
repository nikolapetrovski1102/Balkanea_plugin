/**
 * File Modification Time Checker
 * 
 * This utility script checks when a specific hotel data feed file was last modified.
 * It's used to monitor the freshness of the hotel data feed and ensure it's being
 * updated regularly.
 */
<?php

// Get the full path to the hotel data feed file
$localFilePath = getcwd() . '2024-12-23-feed_en_v3.json.zst';

// Display the last modification time of the file in a human-readable format
echo "Last time of file change: " .  
  date("F d Y H:i:s.", filemtime($localFilePath));

?>