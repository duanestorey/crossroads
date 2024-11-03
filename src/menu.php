<?php

namespace CR;

class Menu {
    var $menu_data = [];
    var $menu_file = null;

    public function __construct() {
        $this->menu_file = \CROSSROAD_BASE_DIR . '/config/menus.yaml';
    }

    public function load_menus() {
        if ( file_exists( $this->menu_file ) ) {
            $this->menu_data = YAML::parse_file( $this->menu_file );
        }
    }

    public function is_available( $menu_name ) {
        return isset( $this->menu_data[ $menu_name ] );
    }

    public function get_available() {
        $available = [];

        foreach( $this->menu_data as $name => $data ) {
            $available[] = $name;
        }

        return $avilable;
    }

    public function build( $menu_name, $current_page ) {
        $menu_data = false;
        if ( isset( $this->menu_data[ $menu_name ] ) ) {
            $menu_data = [];

            foreach( $this->menu_data[ $menu_name ] as $page_name => $page_slug ) {
                $menu_item = new \stdClass;
                $menu_item->name = $page_name;
                $menu_item->url = $page_slug;
                $menu_item->is_active = false;
                
                if ( $current_page == $page_slug ) {
                    $menu_item->is_active = true;
                }

                $menu_data[] = $menu_item;
            }
        }

        return $menu_data;
    }
}