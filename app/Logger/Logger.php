<?php

namespace App\Logger;

class Logger
{
    private static ?Logger $instance = null;

    private array $logs = [];

    private function __construct() {}

    public static function getInstance(): Logger
    {
        if (self::$instance === null) {
            self::$instance = new self;
        }

        return self::$instance;
    }

    public function log(string $log): void
    {
        $this->logs[] = $log;
    }

    public function getLogs(): array
    {
        return $this->logs;
    }
}
