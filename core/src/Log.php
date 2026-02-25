<?php

namespace CR;

class Log
{
    public const DEBUG = 0;
    public const INFO = 1;
    public const WARNING = 2;
    public const ERROR = 3;
    public const FATAL = 10;

    private static $instance = null;
    public $listeners = [];

    protected function __construct()
    {
    }

    public function log($message, $tabs = 0, $level = Log::INFO)
    {
        if (count($this->listeners)) {
            foreach ($this->listeners as $listener) {
                $listener->log($message, $tabs, $level);
            }
        }
    }

    public function installListener($listener)
    {
        $this->listeners[] = $listener;
    }

    public static function instance()
    {
        if (self::$instance == null) {
            self::$instance = new Log();
        }

        return self::$instance;
    }
}

function LOG($message, $tabs = 0, $level = Log::INFO)
{
    Log::instance()->log($message, $tabs, $level);
}
