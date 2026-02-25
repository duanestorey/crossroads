<?php

namespace CR;

class ThemeException extends Exception
{
    public function __construct($msg)
    {
        parent::__construct('THEME', $msg);
    }
}
