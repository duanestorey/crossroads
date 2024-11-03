<?php

namespace CR;

class Builder {
    var $config = null;
    var $start_time = null;
    var $total_pages = 0;
    var $template_engine = null;
    var $entries = null;
    var $theme = null;
    var $menu = null;
    var $plugin_manager = null;
    var $renderer = null;

    public function __construct( $config ) {
        $this->config = $config;

        $this->template_engine = new TemplateEngine();
        $this->template_engine->set_template_dir( CROSSROAD_THEME_DIR . '/' . $config[ 'site' ][ 'theme' ] );

        $this->plugin_manager = new PluginManager( $this->config );
        $this->plugin_manager->install_plugin( new ImagePlugin( $this->config ) );
        $this->plugin_manager->install_plugin( new SeoPlugin( $this->config ) );
        $this->plugin_manager->install_plugin( new WordPressPlugin( $this->config ) );
    }

    public function run() {
        @mkdir( CROSSROAD_PUBLIC_DIR );
        @mkdir( CROSSROAD_PUBLIC_DIR . '/assets' );

        $this->_setup_theme();
        $this->_setup_menus();

        $this->renderer = new Renderer( $this->config, $this->template_engine, $this->plugin_manager, $this->menu, $this->theme );

        $this->start_time = microtime( true );

        // load all content here
        $this->entries = new Entries( $this->config, );
        $this->entries->load_all();

        // do all content filtering
        $all_entries = $this->entries->get_all();
        foreach( $all_entries as $entry ) {
             $entry = $this->plugin_manager->content_filter( $entry );
        }

        // build
        echo "..starting template building\n";
        if ( isset( $this->config[ 'content' ][ 'types' ] ) ) {
            foreach( $this->config[ 'content' ][ 'types' ] as $content_type => $content_config ) {
                $entries = $this->entries->get( $content_type );

                if ( $entries ) {
                    // Make the output directory for html
                    Utils::mkdir( CROSSROAD_PUBLIC_DIR . '/' . $content_type );

                    // Make the output directory for images
                    $image_destination_path = CROSSROAD_PUBLIC_DIR . '/assets/' . $content_type;
                    Utils::mkdir( $image_destination_path );

                    // Where the source content is
                    $content_directory = \CROSSROAD_BASE_DIR . '/content/' . $content_type;
                    
                    foreach( $entries as $entry ) {
                        $this->renderer->render_single_page( $entry, [ $entry->content_type . '-single', $entry->content_type, 'index' ] );
                        $this->total_pages++;
                    }
                }

                // process all content
                usort( $entries, 'CR\cr_sort' );

                if ( isset( $content_config[ 'index' ] ) && $content_config[ 'index' ] ) {
                    $this->renderer->render_index_page( $entries, $content_type, '/' . $content_type, [ 'index' ] );
                }

                if ( $content_type == $this->config[ 'site' ][ 'home' ] ) {
                    $this->renderer->render_index_page( $entries, $content_type, '', [ 'index' ] );
                }

                 // tax
                $tax_terms = $this->entries->get_tax_terms( $content_type );
                if ( count( $tax_terms ) ) {
                    Utils::mkdir( CROSSROAD_PUBLIC_DIR . '/' . $content_type . '/taxonomy' );
                    foreach( $tax_terms as $term ) {
                        Utils::mkdir( CROSSROAD_PUBLIC_DIR . '/' . $content_type . '/taxonomy/' . $term );

                        $entries = $this->entries->get_tax( $content_type, $term );

                        usort( $entries, 'CR\cr_sort' );

                        if ( count( $entries ) ) {
                            $this->renderer->render_index_page( $entries, $content_type, '/' . $content_type . '/taxonomy/' . $term, [ 'index' ] );
                        }
                    }
                }
            }
        }

        $this->_write_robots();
        $this->_write_sitemap_xml();

        $total_time = microtime( true ) - $this->start_time;
        echo "..total page(s) generated, " . $this->total_pages . " - build completed in " . sprintf( "%0.4f", $total_time ) . "s\n";
    }

    private function _write_sitemap_xml() {
        $sitemap_xml  = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
        $sitemap_xml .= "<urlset xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\">\n";

        if ( isset( $this->config[ 'content' ][ 'types' ] ) ) {
            foreach( $this->config[ 'content' ][ 'types' ] as $content_type => $content_config ) {
                $entries = $this->entries->get( $content_type );

                usort( $entries, 'CR\cr_sort' );

                foreach( $entries as $entry ) {
                    $sitemap_xml = $this->_add_sitemap_entry( $sitemap_xml, $entry->url );
                }

                $tax_terms = $this->entries->get_tax_terms( $content_type );
                if ( count( $tax_terms ) ) {
                    $tax_url = $this->config[ 'site' ][ 'url' ] . '/' . $content_type . '/taxonomy';
                    foreach( $tax_terms as $term ) {
                        $sitemap_xml = $this->_add_sitemap_entry( $sitemap_xml, $tax_url . '/' . $term, 'monthly' );
                    }
                }
            }
        }

        $sitemap_xml = $this->_add_sitemap_entry( $sitemap_xml, $this->config[ 'site' ][ 'url' ], 'daily' );

        $sitemap_xml .= "</urlset>\n";

        file_put_contents( CROSSROAD_PUBLIC_DIR . '/sitemap.xml', $sitemap_xml );
    }

    private function _add_sitemap_entry( $sitemap_xml, $url, $freq = 'weekly' ) {
        $sitemap_xml .= "\t<url>\n";
        $sitemap_xml .= "\t\t<loc>" . $url . "</loc>\n";
        $sitemap_xml .= "\t\t<changefreq>" . $freq . "</changefreq>\n";
        $sitemap_xml .= "\t</url>\n";

        return $sitemap_xml;
    }

    private function _write_robots() {
        // write robots
        $robots = "user-agent: *\ndisallow: /assets/css/\ndisallow: /assets/js/\nallow: /\n\nUser-agent: Twitterbot\nallow: /\nSitemap: " . $this->config[ 'site' ][ 'url' ] . "/sitemap.xml";
        file_put_contents( CROSSROAD_PUBLIC_DIR . '/robots.txt', $robots );
        echo "....writing robots.txt\n";
    }

    private function _setup_menus() {
        $this->menu = new Menu();
        $this->menu->load_menus();
    }

    private function _setup_theme() {
        $this->theme = new Theme( $this->config[ 'site' ][ 'theme' ], CROSSROAD_THEME_DIR );
        echo "..attemping to load theme [" . $this->theme->name() . "]\n";

        if ( !$this->theme->is_sane() ) {
            throw new ThemeException( 'Broken theme' );
        }

        $this->theme->load_config();
        echo "....theme successfully loaded\n";

        $this->theme->process_assets( CROSSROAD_PUBLIC_DIR . '/assets' );
    }
}