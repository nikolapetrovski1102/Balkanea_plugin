<?php

$localFilePath = getcwd() . '2024-12-23-feed_en_v3.json.zst';

echo "Last time of file change: " .  
  date("F d Y H:i:s.", filemtime($localFilePath));

?>