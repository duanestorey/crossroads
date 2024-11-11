<?php

namespace CR;

class Config {
    protected $config = null;

    public function __construct( $config ) {
        $this->config = $config;
    }

    public function get( $key, $default = false ) {
        if ( $this->config && isset( $this->config[ $key ] ) ) {
            return $this->config[ $key ];
        } else {
            LOG( sprintf( "Setting not found [%s] in [%s:%d]", $key, debug_backtrace()[0][ 'file' ], debug_backtrace()[0][ 'line' ] ), 1, LOG::WARNING );
            return $default;
        }
    }
}