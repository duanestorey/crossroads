<?php
/*
    All code copyright (c) 2024 by Duane Storey - All rights reserved
    You may use, distribute and modify this code under the terms of GPL version 3.0 license or later
*/

namespace CR;

use MallardDuck\HtmlFormatter\Formatter;

class Renderer {
    const HOME = 0;
    const TAXONOMY = 1;
    const CONTENT = 2;
    const AUTHOR = 3;

    var $config = false;
    var $templateEngine = false;
    var $pluginManager = false;
    var $menu = false;
    var $theme = false;
    var $startTime = false;

    protected $formatter = null;
    
    public function __construct( $config, $templateEngine, $pluginManager, $menu, $theme ) {
        $this->config = $config;
        $this->templateEngine = $templateEngine;
        $this->pluginManager = $pluginManager;
        $this->menu = $menu;
        $this->theme = $theme;

        $this->startTime = time();

        if ( $this->config->get( 'options.beautify_html', false ) ) {
            $this->formatter = new Formatter();
        }
    }

    public function renderSinglePage( $entry, $templateFiles ) {
        // set up page specific stuff like the page titel
        $params = $this->_getDefaultRenderParams( $entry->relUrl, [ $entry->contentType, $entry->contentType . '-' . $entry->className ] );
        $params->content = $entry;
        $params = $this->pluginManager->templateParamFilter( $params );

        $params->page->title = sprintf( '%s - %s', $entry->title, $this->config->get( 'site.name' ) ); 
        $params->isSingle = true;

        $templateName = $this->templateEngine->locateTemplate( $templateFiles );
        if ( $templateName ) {
            $renderedHtml = $this->templateEngine->render( $templateName, $params );

            if ( $this->config->get( 'options.beautify_html' ) ) {
                $renderedHtml = $this->formatter->beautify( $renderedHtml );
            }

            // check directory
            $info = pathinfo( CROSSROADS_PUBLIC_DIR . $params->content->relUrl );
            if ( $info ) {
                if ( !file_exists( $info[ 'dirname' ] ) ) {
                    @mkdir( $info[ 'dirname' ], 0755, true );
                }
            }

            file_put_contents( CROSSROADS_PUBLIC_DIR . $params->content->relUrl, $renderedHtml );

            LOG( sprintf( _i18n( 'core.class.renderer.output' ), CROSSROADS_PUBLIC_DIR . $params->content->relUrl ), 4, LOG::DEBUG );
        }    
    }

    public function renderIndexPage( $entries, $contentType, $path, $templateFiles, $pageType, $pageTax = false, $pageTerm = false ) {
        $totalPages = 0;

        // this is wrong, but fix later
        $contentPerPage = $this->config->get( 'options.content_per_page', 10 ); 

        $pagination = new \stdClass;
        $pagination->currentPage = 1;
        $pagination->curPageLink = '';
        $pagination->prevPageLink = '';
        $pagination->nextPageLink = '';
        
        if ( count( $entries ) % $contentPerPage == 0 ) {
            $pagination->totalPages = intdiv( count( $entries ), $contentPerPage );
        } else {    
            $pagination->totalPages = intdiv( count( $entries ), $contentPerPage ) + 1;
        }

        $pagination->links = $this->_getPaginationLinks( $path, $pagination->totalPages );

        $templateName = $this->templateEngine->locateTemplate( $templateFiles );
        if ( $templateName ) {
            while ( $pagination->currentPage <= $pagination->totalPages ) {
                if ( $pagination->currentPage == 1 ) {
                    $filename = CROSSROADS_PUBLIC_DIR . $path . '/index.html';
                    $pagination->curPageLink = $path . '/index.html';
                } else {
                    $filename = CROSSROADS_PUBLIC_DIR . $path . '/index-page-' . $pagination->currentPage . '.html';
                    $pagination->curPageLink = $path . '/index-page-' . $pagination->currentPage . '.html';
                }

                if ( $pagination->currentPage != $pagination->totalPages ) {
                    $pagination->nextPageLink = $path . '/index-page-' . ( $pagination->currentPage + 1 ). '.html';
                } else {
                    $pagination->nextPageLink = '';
                }

                $is_home = ( $pagination->currentPage == 1 && $path == '' );
                $body_class_array = ( $is_home ? [ 'home' ] : [ $contentType ] );

                $params = $this->_getDefaultRenderParams( $pagination->curPageLink, $body_class_array );
                
                switch( $pageType ) {
                    case Renderer::HOME:
                        if ( $pagination->currentPage == 1 ) {
                            $params->page->title = sprintf( _i18n( 'seo.home' ), $this->config->get( 'site.name' ), $this->config->get( 'site.description' ) ); 
                        } else {
                            $params->page->title = sprintf( _i18n( 'seo.home_paged' ), $pagination->currentPage, $pagination->totalPages, $this->config->get( 'site.name' ) ); 
                        }
                        
                        $params->page->description = $this->config->get( 'site.home_description' ); 
                        
                        break;
                    case Renderer::CONTENT:
                        if ( $pagination->currentPage == 1 ) {
                            $params->page->title = sprintf( _i18n( 'seo.content_home' ), ucwords( $contentType ), $this->config->get( 'site.name' ) ); 
                        } else {
                            $params->page->title = sprintf( _i18n( 'seo.content_paged' ), ucwords( $contentType ), $pagination->currentPage, $pagination->totalPages, $this->config->get( 'site.name' ) ); 
                        }

                        $params->page->description = sprintf( _i18n( 'seo.content_home' ), ucwords( $contentType ), $this->config->get( 'site.description' ) ); 

                        break;
                    case Renderer::TAXONOMY:
                        if ( $pagination->currentPage == 1 ) {
                            $params->page->title = sprintf( _i18n( 'seo.taxonomy_home' ), ucwords( $pageTax ), ucwords( $pageTerm ), $this->config->get( 'site.name' ) ); 
                        } else {
                            $params->page->title = sprintf( _i18n( 'seo.taxonomy_paged' ), ucwords( $pageTax ), ucwords( $pageTerm ), $pagination->currentPage, $pagination->totalPages, $this->config->get( 'site.name' ) ); 
                        }

                        $params->page->description = sprintf( _i18n( 'seo.taxonomy_home' ), ucwords( $pageTax ), ucwords( $pageTerm ), $this->config->get( 'site.description' ) ); 
                       
                        break;
                }
               
                $params->content = array_slice( $entries, ( $pagination->currentPage - 1 ) * $contentPerPage, $contentPerPage );

                $params->isHome = $is_home;
                $params->pagination = $pagination;
                
                $renderedHtml = $this->templateEngine->render( $templateName, $params );
                
                if ( $this->config->get( 'options.beautify_html' ) ) {
                    $renderedHtml = $this->formatter->beautify( $renderedHtml );
                }
                
                file_put_contents( $filename, $renderedHtml );  

                LOG( sprintf( _i18n( 'core.class.renderer.output' ), CROSSROADS_PUBLIC_SLUG . $pagination->curPageLink ), 3, LOG::DEBUG );

                $totalPages++;

                $pagination->currentPage++;

                $pagination->prevPageLink = $pagination->curPageLink;
            }
        }    

        return $totalPages;
    }

    private function _getPaginationLinks( $path, $totalPages ) {
        $links = array();

        for ( $i = 0; $i < $totalPages; $i++ ) {
            $page = new \stdClass;

            $page->num = $i + 1;
            $page->url = $i == 0 ? $path . '/index.html' : $path . '/index-page-' . ( $i+1 ) . '.html';

            $links[] = $page;
        }

        return $links;
    }    

    private function _getDefaultRenderParams( $currentPage, $bodyClasses = [] ) {
        $params = new \stdClass;
        $params->site = new \stdClass;
        $params->site->title = $this->config->get( 'site.name' ); 

        $params->site->lang = $this->config->get( 'site.lang', 'en' );
        $params->site->charset = $this->config->get( 'site.charset', 'utf-8' );

        foreach( $this->menu->getAvailable() as $name ) {
            $params->menu[ $name ] = $this->menu->build( $name, $currentPage );
        }

        $params->page = new \stdClass;
        $params->page->assetUrl = '/assets';
        $params->page->assetHash = $this->theme->getAssetHash();
        $params->page->bodyClassesRaw = $bodyClasses;
        $params->page->bodyClasses = implode( ' ', $params->page->bodyClassesRaw );    
        $params->page->title = '';

        $params->isSingle = false;
        $params->isHome = false;

        $params->renderTime = $this->startTime;

        return $params;
    }    
}