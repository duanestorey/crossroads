<?php

namespace CR;

class SeoPlugin extends Plugin {
    var $config = null;

    public function __construct( $config ) {
        parent::__construct( 'seo' );

        $this->config = $config;
    }

    public function processOne( $content ) {
        return $content;
    }    
}