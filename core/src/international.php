<?php

namespace CR;

class International {
    private static $instance = null;
    protected $strings = [];

    protected function __construct() {}

    static function instance() {
        if ( self::$instance == null ) {
            self::$instance = new International();
        }

        return self::$instance;
    }   

    public function loadLocaleFile( $file ) {
        $strings = YAML::parse_file( $file, true );

        if ( $strings ) {
            $this->strings = array_merge( $this->strings, $strings );

            ksort( $this->strings );
        }
    }

    protected function getCallingFunction() {
        $trace = debug_backtrace( DEBUG_BACKTRACE_PROVIDE_OBJECT );
        if ( $trace ) {
            $func = '';
            if ( isset( $trace[ 3 ][ 'class' ] ) ) {
                $func .= $trace[ 3 ]['class'];
            }

            if ( isset( $trace[ 3 ][ 'function' ] ) ) {
                $func .= '::' . $trace[ 3 ][ 'function' ];
            }

            if ( isset( $trace[ 3 ][ 'line' ] ) ) {
                $func .= ' (' . $trace[ 3 ][ 'line' ] . ')';
            }

            return $func;
        }
    }

    public function get( $name ) {
        if ( isset( $this->strings[ $name ] ) ) {
            return $this->strings[ $name ];
        } else {
            LOG( sprintf( "Unable to find i18n string [%s] in [%s]", $name, $this->getCallingFunction() ), 1, LOG::WARNING );
        }

        return '';
    }
}

function _i18n( $str ) {
    return International::instance()->get( $str );
}