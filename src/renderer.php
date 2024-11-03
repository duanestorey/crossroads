<?php

namespace CR;

class Renderer {
    var $config = false;
    var $template_engine = false;
    var $plugin_manager = false;
    var $menu = false;
    var $theme = false;
    var $start_time = false;
    
    public function __construct( $config, $template_engine, $plugin_manager, $menu, $theme ) {
        $this->config = $config;
        $this->template_engine = $template_engine;
        $this->plugin_manager = $plugin_manager;
        $this->menu = $menu;
        $this->theme = $theme;

        $this->start_time = time();
    }

    public function render_single_page( $entry, $template_files ) {
        // set up page specific stuff like the page titel
        $params = $this->_get_default_render_params( $entry->content_type, $entry->slug, [ $entry->content_type . '-' . $entry->class_name ] );
        $params->content = $entry;
        $params = $this->plugin_manager->template_param_filter( $params );

        $params->is_single = true;

        $template_name = $this->template_engine->locate_template( $template_files );
        if ( $template_name ) {
            $rendered_html = $this->template_engine->render( $template_name, $params );
            file_put_contents( CROSSROAD_PUBLIC_DIR . $params->content->slug, $rendered_html );

            echo "......outputting template file " . CROSSROAD_PUBLIC_DIR . $params->content->slug . "\n";
        }    
    }

    public function render_index_page( $entries, $content_type, $path, $template_files ) {
        // this is wrong, but fix later
        $content_per_page = 10;
        if ( isset( $this->config[ 'options' ][ 'content_per_page' ] ) ) {
            $content_per_page = $this->config[ 'options' ][ 'content_per_page' ];
        }

        $pagination = new \stdClass;
        $pagination->current_page = 1;
        $pagination->cur_page_link = '';
        $pagination->prev_page_link = '';
        $pagination->next_page_link = '';
        
        if ( count( $entries ) % $content_per_page == 0 ) {
            $pagination->total_pages = intdiv( count( $entries ), $content_per_page );
        } else {    
            $pagination->total_pages = intdiv( count( $entries ), $content_per_page ) + 1;
        }

        $pagination->links = $this->_get_pagination_links( $path, $pagination->total_pages );

        $template_name = $this->template_engine->locate_template( $template_files );
        if ( $template_name ) {
            while ( $pagination->current_page <= $pagination->total_pages ) {
                if ( $pagination->current_page == 1 ) {
                    $filename = CROSSROAD_PUBLIC_DIR . $path . '/index.html';
                    $pagination->cur_page_link = $path . '/index.html';
                } else {
                    $filename = CROSSROAD_PUBLIC_DIR . $path . '/index-page-' . $pagination->current_page . '.html';
                    $pagination->cur_page_link = $path . '/index-page-' . $pagination->current_page . '.html';
                }

                if ( $pagination->current_page != $pagination->total_pages ) {
                    $pagination->next_page_link = $path . '/index-page-' . ( $pagination->current_page + 1 ). '.html';
                } else {
                    $pagination->next_page_link = '';
                }

                $is_home = ( $pagination->current_page == 1 && $path == '' );
                $body_class_array = ( $is_home ? [ 'home' ] : [] );

                $params = $this->_get_default_render_params($content_type, $pagination->cur_page_link, $body_class_array );
                
                $params->page->title = $this->config[ 'site' ][ 'title' ];
                $params->page->description = $this->config[ 'site' ][ 'description' ];
                $params->content = array_slice( $entries, ( $pagination->current_page - 1 ) * $content_per_page, $content_per_page );

                $params->is_home = $is_home;
                $params->pagination = $pagination;

                $rendered_html = $this->template_engine->render( $template_name, $params );
                file_put_contents( $filename, $rendered_html );  

                echo "......outputting template file " . $filename . "\n";

                $pagination->current_page++;

                $pagination->prev_page_link = $pagination->cur_page_link;
            }
        }    
    }

    private function _get_pagination_links( $path, $total_pages ) {
        $links = array();

        for ( $i = 0; $i < $total_pages; $i++ ) {
            $page = new \stdClass;

            $page->num = $i + 1;
            $page->url = $i == 0 ? $path . '/index.html' : $path . '/index-page-' . ( $i+1 ) . '.html';

            $links[] = $page;
        }

        return $links;
    }    

    private function _get_default_render_params( $content_type, $current_page, $extra_body_classes = [] ) {
        $params = new \stdClass;
        $params->site = new \stdClass;
        $params->site->title = $this->config[ 'site' ][ 'name' ];

        $params->site->lang = 'en';
        if ( isset( $this->config[ 'site' ][ 'lang' ] ) ) {
            $params->site->lang = $this->config[ 'site' ][ 'lang' ];
        }

        $params->site->charset = 'utf-8';
        if ( isset( $this->config[ 'site' ][ 'charset' ] ) ) {
            $params->site->charset = $this->config[ 'site' ][ 'charset' ];
        }

        $params->menu = $this->menu->build( 'main', $current_page );

        $params->page = new \stdClass;
        $params->page->asset_url = '/assets';
        $params->page->asset_hash = $this->theme->get_asset_hash();
        $params->page->body_classes_raw = array_merge( [ $content_type ], $extra_body_classes );
        $params->page->body_classses = implode( ' ', $params->page->body_classes_raw );    

        $params->is_single = false;
        $params->is_home = false;

        $params->render_time = $this->start_time;

        return $params;
    }    
}