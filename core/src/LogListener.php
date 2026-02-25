<?php

namespace CR;

abstract class LogListener
{
    abstract public function setLevel($level);
    abstract public function log($message, $tabs, $level);
}
