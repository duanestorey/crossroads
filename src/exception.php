<?php

namespace CR;

class Exception extends \Exception {
    var $name = null;
    var $msg = null;

    public function __construct( $name, $msg ) {
        $this->name = $name;
        $this->msg = $msg;
    }

    public function name() {
        return $this->name;
    }

    public function msg() {
        return $this->msg;
    }
}

class ThemeException extends Exception {
    public function __construct( $msg ) {
        parent::__construct( 'THEME', $msg );
    }
}

class SassException extends Exception {
    public function __construct( $msg ) {
        parent::__construct( 'SASS', $msg );
    }
}

class BuildException extends Exception {
    public function __construct( $msg ) {
        parent::__construct( 'BUILD', $msg );
    }
}