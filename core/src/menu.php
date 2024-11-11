<?php

namespace CR;

class Menu {
    var $menuData = [];
    var $menuFile = null;

    public function __construct() {
        $this->menuFile = \CROSSROADS_BASE_DIR . '/_config/menus.yaml';
    }

    public function loadMenus() {
        if ( file_exists( $this->menuFile ) ) {
            $this->menuData = YAML::parse_file( $this->menuFile );
        }
    }

    public function isAvailable( $menuName ) {
        return isset( $this->menuData[ $menuName ] );
    }

    public function getAvailable() {
        $available = [];

        foreach( $this->menuData as $name => $data ) {
            $available[] = $name;
        }

        return $avilable;
    }

    public function build( $menuName, $currentPage ) {
        $menuData = false;
        if ( isset( $this->menuData[ $menuName ] ) ) {
            $menuData = [];

            foreach( $this->menuData[ $menuName ] as $pageName => $pageSlug ) {
                $menuItem = new \stdClass;
                $menuItem->name = $pageName;
                $menuItem->url = $pageSlug;
                $menuItem->isActive = false;
                
                if ( $currentPage == $pageSlug ) {
                    $menuItem->isActive = true;
                }

                $menuData[] = $menuItem;
            }
        }

        return $menuData;
    }
}