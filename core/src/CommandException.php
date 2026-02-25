<?php

namespace CR;

class CommandException extends Exception
{
    public function __construct($msg)
    {
        parent::__construct('COMMAND', $msg);
    }
}
