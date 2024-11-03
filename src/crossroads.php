<?php

namespace CR;

define( 'CROSSROADS_VERSION', '1.00' );

require_once( 'src/builder.php' );
require_once( 'src/entries.php' );
require_once( 'src/markdown.php' );
require_once( 'src/template-engine.php' );
require_once( 'src/exception.php' );
require_once( 'src/content.php' );
require_once( 'src/menu.php' );
require_once( 'src/yaml.php' );
require_once( 'src/theme.php' );
require_once( 'src/utils.php' );
require_once( 'src/plugin.php' );
require_once( 'src/plugin-manager.php' );
require_once( 'src/renderer.php' );

require_once( 'plugins/image-plugin.php' );
require_once( 'plugins/seo-plugin.php' );
require_once( 'plugins/wordpress-plugin.php' );

class Engine {
    var $builder = null;
    var $config = null;

    public function __construct() {}

    public function run( $argc, $argv ) {
        $this->_branding();

        $this->_load_config();

        if ( $argc == 1 ) {
            $this->_usage();
        } else {
            switch( $argv[ 1 ] ) {
                case 'build':
                    $this->_build();
                    break;
                case 'serve':
                    $this->_serve();
                    break;
            }
        }
    }

    private function _load_config() {
        $this->config = YAML::parse_file( CROSSROAD_BASE_DIR . '/config/site.yaml' );
    }

    private function _check_config() {
        // check to make sure everything we need is here
    }

    private function _branding() {
        echo "Crossroads " . CROSSROADS_VERSION . " starting up\n";
    }

    private function _usage() {
        echo "..proper usage:\n\n";
        echo "php crossroads.php build      Builds entire website\n";
        echo "php crossroads.php serve      Start webserver\n";
    }

    private function _build() {
        echo "..building website\n";

        $this->builder = new Builder( $this->config );

        try {
            $this->builder->run();
        } catch( Exception $e ) {
            echo "..build stopped due to exception [" . $e->name() . "] with message [" . $e->msg() . "]\n";
        }
        
    }

    private function _serve() {
        echo "..building website\n";
    }
}