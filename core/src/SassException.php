<?php

namespace CR;

class SassException extends Exception
{
    public function __construct($msg)
    {
        parent::__construct('SASS', $msg);
    }
}
