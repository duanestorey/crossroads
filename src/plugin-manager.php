<?php

namespace CR;

class PluginManager extends Plugin {
    var $config = null;
    var $plugins = [];
    
    public function __construct( $config ) {
        $this->config = $config;
    }

    public function install_plugin( $plugin ) {
        $this->plugins[] = $plugin;
    }

    public function content_filter( $content ) {
        if ( count( $this->plugins ) ) {
            foreach( $this->plugins as $plugin ) {
                $content = $plugin->content_filter( $content );
            }
        }

        return $content;
    }

    public function template_param_filter( $params ) {
        if ( count( $this->plugins ) ) {
            foreach( $this->plugins as $plugin ) {
                $params = $plugin->template_param_filter( $params );
            }
        }

        return $params;
    }
} 