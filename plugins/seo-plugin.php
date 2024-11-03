<?php

namespace CR;

class SeoPlugin extends Plugin {
    var $config = null;

    public function __construct( $config ) {
        $this->config = $config;
    }

    public function content_filter( $content ) {
        return $content;
    }    

    public function template_param_filter( $params ) {
        $params->page->title = sprintf( "%s - %s", $params->content->title, $this->config[ 'site' ][ 'name' ] );

        if ( !$params->content->description ) {
            $params->content->description = $params->content->excerpt( 120, false );
        }

        return $params;
    }

}