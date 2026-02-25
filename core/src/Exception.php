<?php

namespace CR;

class Exception extends \Exception
{
    public $name = null;
    public $msg = null;

    public function __construct($name, $msg)
    {
        $this->name = $name;
        $this->msg = $msg;
    }

    public function name()
    {
        return $this->name;
    }

    public function msg()
    {
        return $this->msg;
    }
}
