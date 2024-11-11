<?php

namespace CR;

class PluginManager extends Plugin {
    var $config = null;
    var $plugins = [];
    
    public function __construct( $config ) {
        $this->config = $config;
    }

    public function installPlugin( $plugin ) {
        $this->plugins[] = $plugin;
    }

    public function contentFilter( $content ) {
        if ( count( $this->plugins ) ) {
            foreach( $this->plugins as $plugin ) {
                $content = $plugin->contentFilter( $content );
            }
        }

        return $content;
    }

    public function templateParamFilter( $params ) {
        if ( count( $this->plugins ) ) {
            foreach( $this->plugins as $plugin ) {
                $params = $plugin->templateParamFilter( $params );
            }
        }

        return $params;
    }

    public function processOne( $entry ) {
        if ( count( $this->plugins ) ) {
            foreach( $this->plugins as $plugin ) {
                return $plugin->processOne( $entry );
            }
        }   

        return false;
    }

    public function processAll( $entries ) {
        if ( count( $this->plugins ) ) {
            foreach( $this->plugins as $plugin ) {
                LOG( sprintf( _i18n( 'core.build.plugins.executing' ), $plugin->name() ), 2, LOG::INFO );

                $entries = $plugin->processAll( $entries );
            }
        }

        return $entries;       
    }

} 