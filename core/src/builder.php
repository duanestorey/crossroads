<?php
/*
    All code copyright (c) 2024 by Duane Storey - All rights reserved
    You may use, distribute and modify this code under the terms of GPL version 3.0 license or later
*/

namespace CR;

class Builder {
    var $config = null;
    var $totalPages = 0;

    var $templateEngine = null;
    var $entries = null;
    var $theme = null;
    var $menu = null;
    var $pluginManager = null;
    var $renderer = null;

    protected $db;

    public function __construct( $config, $pluginManager, $db ) {
        $this->config = $config;
        $this->pluginManager = $pluginManager;
        $this->db = $db;
    }

    public function run() {
        @mkdir( CROSSROADS_PUBLIC_DIR );
        @mkdir( CROSSROADS_PUBLIC_DIR . '/assets' );

        $this->_setupTheme();
        $this->_setupMenus();

        $this->templateEngine = new TemplateEngine( $this->config );

        if ( $this->theme->isChildTheme() ) {
             $this->templateEngine->setTemplateDirs( 
                [
                    CROSSROADS_LOCAL_THEME_DIR . '/' . $this->theme->getChildThemeName(),
                    CROSSROADS_BASE_DIR . '/' . $this->config->get( 'dirs.core_themes', 'core/themes' ) . '/' . $this->theme->getParentThemeName()
                ]
            );       
        } else {
            if ( $this->theme->isLocalTheme() ) {
                $this->templateEngine->setTemplateDirs( 
                    [
                        CROSSROADS_LOCAL_THEME_DIR . '/' . $this->theme->getChildThemeName()
                    ]
                );
            } else {
                $this->templateEngine->setTemplateDirs( 
                    [
                        CROSSROADS_BASE_DIR . '/' . $this->config->get( 'dirs.core_themes', 'core/themes' ) . '/' . $this->config->get( 'site.theme' ) 
                    ]
                );           
            }

        }
 

        $this->renderer = new Renderer( $this->config, $this->templateEngine, $this->pluginManager, $this->menu, $this->theme );

        // load all content here
        $this->entries = new Entries( $this->config, $this->db, $this->pluginManager );
        $this->entries->loadAll();

        // do all content filtering
        $all_entries = [];

        foreach( $this->config->get( 'content', [] ) as $contentType => $contentConfig ) {
            $entries = $this->entries->get( $contentType );

            if ( $entries ) {
                LOG( sprintf( _i18n( 'core.build.generating.single' ), $contentType ), 1, LOG::INFO );

                // Make the output directory for html
                Utils::mkdir( CROSSROADS_PUBLIC_DIR . '/' . $contentType );

                // Make the output directory for images
                $image_destination_path = CROSSROADS_PUBLIC_DIR . '/assets/' . $contentType;
                Utils::mkdir( $image_destination_path );

                // Where the source content is
                $content_directory = \CROSSROADS_BASE_DIR . '/content/' . $contentType;
                
                foreach( $entries as $entry ) {
                    LOG( "Writing content for [" . $entry->relUrl . "]", 2, LOG::DEBUG );

                    $this->renderer->renderSinglePage( $entry, [ $entry->contentType . '-single', $entry->contentType, 'index' ] );
                    $this->totalPages++;                
                }
            }

            // process all content
            usort( $entries, 'CR\cr_sort' );

            if ( isset( $contentConfig[ 'index' ] ) && $contentConfig[ 'index' ] ) {
                LOG( sprintf( _i18n( 'core.build.generating.index' ), $contentType ), 1, LOG::INFO );

                $this->totalPages += $this->renderer->renderIndexPage( 
                    $entries, 
                    $contentType, 
                    '/' . $contentType, 
                    [ 'index' ], 
                    Renderer::CONTENT 
                );
            }

            if ( $contentType == $this->config->get( 'site.home' ) ) {
                LOG( sprintf( _i18n( 'core.build.generating.home' ), $contentType ), 1, LOG::INFO );

                $this->totalPages += $this->renderer->renderIndexPage( 
                    $entries, 
                    $contentType, 
                    '', 
                    [ 'index' ], 
                    Renderer::HOME 
                );
            }

             // tax
            $taxTypes = $this->entries->getTaxTypes( $contentType );
            sort( $taxTypes );
            if ( count( $taxTypes ) ) {
                foreach( $taxTypes as $taxType ) {
                    $taxTerms = $this->entries->getTaxTerms( $contentType, $taxType );

                    LOG( sprintf( _i18n( 'core.build.generating.tax' ), $contentType . '/' . $taxType ), 1, LOG::INFO );

                    Utils::mkdir( CROSSROADS_PUBLIC_DIR . '/' . $contentType . '/' . $taxType );

                    foreach( $taxTerms as $term ) {
                        Utils::mkdir( CROSSROADS_PUBLIC_DIR . '/' . $contentType . '/' . $taxType . '/' . $term );

                        LOG( sprintf( _i18n( 'core.build.generating.tax' ), $contentType . "/" . $term ), 2, LOG::DEBUG );

                        $entries = $this->entries->getTax( $contentType, $taxType, $term );

                        usort( $entries, 'CR\cr_sort' );

                        if ( count( $entries ) ) {
                            $this->totalPages += $this->renderer->renderIndexPage( 
                                $entries, 
                                $contentType, '/' . $contentType . '/' . $taxType . '/' . 
                                $term, [ 'index' ],
                                Renderer::TAXONOMY,
                                $taxType,
                                $term
                            );
                        }
                    }              
                }
            }
        }

        $this->_writeRobots();
        $this->_writeSitemapXml();

        LOG( sprintf( _i18n( 'core.build.total' ), $this->entries->getEntryCount(), $this->totalPages ), 0, LOG::INFO );

        LOG( _i18n( 'core.build.done' ), 0, LOG::INFO );
    }

    private function _writeSitemapXml() {
        $sitemapXml  = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
        $sitemapXml .= "<urlset xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\">\n";

        foreach( $this->config->get( 'content', [] ) as $contentType => $contentConfig ) {
            $entries = $this->entries->get( $contentType );

            usort( $entries, 'CR\cr_sort' );

            foreach( $entries as $entry ) {
                $sitemapXml = $this->_addSitemapEntry( $sitemapXml, $entry->url );
            }

            $taxTypes = $this->entries->getTaxTypes( $contentType );
            if ( count( $taxTypes ) ) {
                foreach( $taxTypes as $taxType ) {
                    $taxTerms = $this->entries->getTaxTerms( $contentType, $taxType );
                    if ( count( $taxTerms ) ) {
                        $taxUrl = $this->config->get( 'site.url' ) . '/' . $contentType . '/' . $taxType;
                        foreach( $taxTerms as $term ) {
                            $sitemapXml = $this->_addSitemapEntry( $sitemapXml, $taxUrl . '/' . $term, 'monthly' );
                        }
                    }               
                }
            }
        }

        $sitemapXml = $this->_addSitemapEntry( $sitemapXml, $this->config->get( 'site.url'), 'daily' );

        $sitemapXml .= "</urlset>\n";

        file_put_contents( CROSSROADS_PUBLIC_DIR . '/sitemap.xml', $sitemapXml );

        LOG( sprintf( _i18n( 'core.build.writing' ), "sitemap.xml" ), 1, LOG::INFO );
    }

    private function _addSitemapEntry( $sitemapXml, $url, $freq = 'weekly' ) {
        $sitemapXml .= "\t<url>\n";
        $sitemapXml .= "\t\t<loc>" . $url . "</loc>\n";
        $sitemapXml .= "\t\t<changefreq>" . $freq . "</changefreq>\n";
        $sitemapXml .= "\t</url>\n";

        return $sitemapXml;
    }

    private function _writeRobots() {
        // write robots
        $robots = "user-agent: *\ndisallow: /assets/css/\ndisallow: /assets/js/\nallow: /\n\nUser-agent: Twitterbot\nallow: /\nSitemap: " . $this->config->get( 'site.url') . "/sitemap.xml";
        file_put_contents( CROSSROADS_PUBLIC_DIR . '/robots.txt', $robots );

        LOG( sprintf( _i18n( 'core.build.writing' ), "robots.txt" ), 1, LOG::INFO );
    }

    public function _write404Page() {
        $this->renderer->render404Page();

        LOG( sprintf( _i18n( 'core.build.writing' ), "404.html" ), 1, LOG::INFO );
    }

    private function _setupMenus() {
        $this->menu = new Menu();
        $this->menu->loadMenus();
    }

    private function _setupTheme() {
        $this->theme = new Theme( 
            $this->config->get( 'site.theme' ), 
            CROSSROADS_BASE_DIR . '/' . $this->config->get( 'dirs.core_themes', 'core/themes' ),
            CROSSROADS_LOCAL_THEME_DIR
        );

        LOG( sprintf( _i18n( "core.theme.load" ), $this->theme->name() ), 1, LOG::INFO );

        if ( !$this->theme->load() ) {
            throw new ThemeException( 'Broken theme' );
        }

        LOG( _i18n( "core.theme.loaded" ), 2, LOG::INFO );

        $this->theme->processAssets();
    }
}