<?php
/**
 * Custom region-based logging function
 * Creates region-specific log files and writes messages with timestamps
 * 
 * @param string $message The message to log
 * @param string $region The current region being processed
 * @param string $level Log level (INFO, ERROR, WARNING, DEBUG)
 * @return void
 */


    function logToRegionFile($message, $currentRegion = 'global', $logLevel = 'INFO') {
        $logDate = date('Y-m-d H:i:s');
        $logMessage = "[$logDate] [$logLevel] $message" . PHP_EOL;
        
        $logFilePath = __DIR__ . "/logs/{$currentRegion}_logs.log";
        
        if (!is_dir(__DIR__ . '/logs')) {
            mkdir(__DIR__ . '/logs', 0755, true);
        }
        
        file_put_contents($logFilePath, $logMessage, FILE_APPEND);
    }
    