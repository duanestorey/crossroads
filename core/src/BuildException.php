<?php

namespace CR;

class BuildException extends Exception
{
    public function __construct($msg)
    {
        parent::__construct('BUILD', $msg);
    }
}
