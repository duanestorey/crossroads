<?php

namespace CR;

class Plugin {
    protected $name = 'base';

    public function __construct( $name ) {
        $this->name = $name;
    }

    public function name() {
        return $this->name;
    }

    public function templateParamFilter( $params ) {
        return $params;
    }

    public function processOne( $entry ) {
        return $entry;
    }

    public function processAll( $entries ) {
        $processed = [];

        foreach( $entries as $entry ) {
            $processed[] = $this->processOne( $entry );
        }

        return $processed;        
    }
}