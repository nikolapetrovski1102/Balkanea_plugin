<?php

class Log
{
    protected string $file;

    public function __construct(string $region)
    {
        $this->file = __DIR__ . "/logs/region_$region.log";

        // Create the log file if it doesn't exist
        if (!file_exists($this->file)) {
            file_put_contents($this->file, "Logging to region: $region");
        }
    }

    public function write(string $message, string $level = 'INFO'): void
    {
        $date = date('Y-m-d H:i:s');
        $entry = "[$date][$level] $message" . PHP_EOL;
        file_put_contents($this->file, $entry, FILE_APPEND);
    }

    public function info(string $message): void
    {
        $this->write($message, 'INFO');
    }

    public function warning(string $message): void
    {
        $this->write($message, 'WARNING');
    }

    public function error(string $message): void
    {
        $this->write($message, 'ERROR');
    }
}
