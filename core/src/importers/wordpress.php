<?php

namespace CR\Importers;

use CR\LOG;
use League\HTMLToMarkdown\HtmlConverter;

class WordPress {
    var $perPage = 50;

    public function __construct() {}

    public function import( $homeUrl ) {
        \CR\LOG( "Starting import of WordPress site at [" . $homeUrl . "]", 0, LOG::INFO );

        $wpJsonUrl = \CR\Utils::fixPath( $homeUrl )  . '/wp-json/wp/v2';

        $converter = new HtmlConverter( array('header_style'=>'atx') );
        $converter->getConfig()->setOption('strip_tags', true);

        @mkdir( CROSSROADS_CONTENT_DIR );

        foreach( $this->_contentTypes() as $contentType ) {
            $totalEntries = 0;

            \CR\LOG( "Processing WordPress content of type [" . $contentType . "]", 1, LOG::INFO );

            $contentUrl = $wpJsonUrl . '/' . $contentType . '?_embed&per_page=' . $this->perPage; 
            $currentPage = 1;

            $contentDir = CROSSROADS_CONTENT_DIR . '/' . $contentType;
            @mkdir( $contentDir );

            $brokenImages = [];

            $imageDir = CROSSROADS_CONTENT_DIR . '/' . $contentType . '/_images';
            \CR\Utils::mkDir( $imageDir );

            while ( true ) {
                $content = \CR\Utils::curlDownloadFile( $contentUrl . '&page=' . $currentPage );
                if ( $content ) {
                    \CR\LOG( "Processing page [" . $currentPage . "], entries processed so far [" . $totalEntries . "]", 2, LOG::INFO );

                    $decoded = json_decode( $content );

                    if ( isset( $decoded->data ) && isset( $decoded->data->status ) && $decoded->data->status == 400 ) {
                        break;
                    } 

                    if ( $decoded && count( $decoded ) ) {
                        foreach( $decoded as $entry ) {
                            $wp_entry = new \stdClass;
                            $wp_entry->publishDate = strtotime( $entry->date_gmt );
                            $wp_entry->modifiedDate = strtotime( $entry->modified_gmt );
                            $wp_entry->permalink = $entry->slug;
                            $wp_entry->status = $entry->status;
                            $wp_entry->title = html_entity_decode( $entry->title->rendered );
                            $wp_entry->author = false;
                            $wp_entry->featured = false;
                            $wp_entry->taxonomy = [];

                            \CR\LOG( "Processing individual entry with slug [" . $wp_entry->permalink . "]", 3, LOG::DEBUG );

                            if ( isset( $entry->_embedded ) ) {
                                if ( isset( $entry->_embedded->author ) ) {
                                    $wp_entry->author = $entry->_embedded->author[ 0 ]->name;
                                }

                                if ( isset( $entry->_embedded->{'wp:featuredmedia'} ) ) {
                                    if ( isset( $entry->_embedded->{'wp:featuredmedia'}[ 0 ]->media_details ) ) {
                                        $wp_entry->featured = $entry->_embedded->{'wp:featuredmedia'}[ 0 ]->media_details->file; 
                                    }
                                }   

                                if ( isset( $entry->_embedded->{'wp:term'} ) ) {
                                    foreach( $entry->_embedded->{'wp:term'} as $num => $tax ) {
                                        //print_r( $terms );
                                        foreach( $tax as $one_term ) {
                                            $taxFullName = $one_term->taxonomy;
                                            if ( $taxFullName == 'post_tag' ) {
                                                $taxFullName = 'tag';
                                            }

                                            $wp_entry->taxonomy[ $taxFullName ][] = html_entity_decode ( $one_term->name );
                                        }
                                    }
                                }
                            }

                            $fileSlug = str_replace( ' ', '-', strtolower( preg_replace("/[^a-zA-Z0-9 ]/", "", $wp_entry->title ) ) );
                            $filename = $contentDir . '/' . $wp_entry->permalink . '.md';
                            
                           
                            if ( $contentType == 'posts' ) {
                                $filename = $contentDir . '/' . date( 'Y-m-d', $wp_entry->publishDate ) . '-' . $fileSlug . '.md';
                                $mdFile = $contentType . '/' . $wp_entry->permalink . '.md';
                            } else {
                                $mdFile = $contentType . '/' . date( 'Y-m-d', $wp_entry->publishDate ) . '/' . $wp_entry->permalink . '.md';
                            }
                          
                            $file_content = "---\n";
                            $file_content .= sprintf( "%s: \"%s\"\n", 'title', $this->_escapeYaml( $wp_entry->title ) );
                            $file_content .= sprintf( "%s: \"%s\"\n", 'publishDate', date( "Y-m-d", $wp_entry->publishDate ) );
                            $file_content .= sprintf( "%s: \"%s\"\n", 'modifiedDate', date( "Y-m-d", $wp_entry->modifiedDate ) );
                            $file_content .= sprintf( "%s: \"%s\"\n", 'slug', $this->_escapeYaml( $wp_entry->permalink ) );

                            if ( $wp_entry->author ) {
                                $file_content .= sprintf( "%s: \"%s\"\n", 'author', $this->_escapeYaml( $wp_entry->author ) );
                            }

                            if ( $wp_entry->featured ) {
                                $result = $this->maybeDownloadFile( $wp_entry->featured, $imageDir, $homeUrl, $fileSlug . '-featured' );
                                if ( $result ) {
                                    $file_content .= sprintf( "%s: \"%s\"\n", 'featuredImage', '_images/' . $result );
                                } else {
                                    $brokenImages[ $mdFile ][] = $wp_entry->featured;
                                }
                            }

                            // find all images
                            $regex = '<img[^>]+src="([^">]+)"';
                            if ( preg_match_all( "/$regex/iU", $entry->content->rendered, $matches ) ) {
                                foreach( $matches[ 1 ] as $num => $image ) {
                                    $new_image = str_replace( 'www.duanestorey.com', 'old.duanestorey.com', $image );
                                    $new_image = str_replace( 'www.migratorynerd.com/wordpress/', 'old.duanestorey.com/', $new_image );
                                    $new_image = str_replace( 'www.migratorynerd.com/', 'old.duanestorey.com/', $new_image );
                                    $new_image = str_replace( '/duanestorey.com/wordpress/', '/duanestorey.com/', $new_image );
                                    $new_image = str_replace( '/duanestorey.com', '/old.duanestorey.com', $new_image );
                                    $new_image = str_replace( '/old.duanestorey.com/wordpress/', '/old.duanestorey.com/', $new_image );

                                    // let's remove the shit from the end
                                    $fullInfo = parse_url( $new_image );
                                    $imageFilename = basename( $fullInfo[ 'path' ] );
                                    if ( preg_match( '#(.*)(-(\d+)x(\d+)).(.*)#', $imageFilename, $match ) ) {
                                        $new_image = str_replace( $match[ 2 ], '', $new_image );
                                    }

                                    $result = $this->maybeDownloadFile( $new_image, $imageDir, $homeUrl, $fileSlug . '-' . ( $num + 1 ) );
                                    if ( $result ) {
                                        $entry->content->rendered = str_replace( $image, '_images/' . $result, $entry->content->rendered );
                                    } else {
                                        $brokenImages[ $mdFile ][] = $new_image;
                                    }
                                }
                            }

                            if ( count( $wp_entry->taxonomy ) ) {
                                foreach( $wp_entry->taxonomy as $taxName => $taxContent ) {
                                    $taxFrontMatter = $this->_escapeYaml( $taxName ) . ":\n";
                                    foreach( $taxContent as $taxNum => $taxTerm ) {
                                        $taxFrontMatter = $taxFrontMatter . "  - \"" . $this->_escapeYaml( $taxTerm ) . "\"\n";
                                    }

                                    $file_content .= $taxFrontMatter;
                                }
                            }

                            $wp_entry->markdown = $converter->convert( html_entity_decode( $entry->content->rendered ) );

                            $file_content .= "---\n\n";

                            $file_content .= $wp_entry->markdown;

                            file_put_contents( $filename, $file_content );

                            $totalEntries++;
                        }
                    }
                    $currentPage++;
                } else {
                    break; 
                }
            }

            \CR\LOG( "Finished processing content of type [" . $contentType . "], entries processed [" . $totalEntries . "]", 1, LOG::INFO );

            // output broken images
            if ( count( $brokenImages ) ) {
                foreach( $brokenImages as $slug => $images ) {
                    foreach( $images as $image ) {
                        \CR\LOG( "Broken image for entry [" . $slug . "], image [" . $image . "]", 2, LOG::WARNING );
                    }
                }
            }
        }
    }

    private function _escapeYaml( $str ) {
        return str_replace( "\"", "\\\"", $str );
    }

    private function hasHttpOrHttps( $url ) {
        return ( strpos( $url, 'http://' ) !== false ) || ( strpos( $url, 'https://' ) !== false );
    }

    private function maybeDownloadFile( $url, $destPath, $wpUrl, $renameSlug = false ) {
        $origUrl = $url;

        if ( strpos( $url, 'www.assoc-amazon.com' ) !== false ) {
          //  echo "......skipping image " . $url . "\n";
            return $url;
        }

        \CR\LOG( "Processing image [" . $url . "]", 3, LOG::DEBUG );

        if ( !$this->hasHttpOrHttps( $url ) ) {
            $url = $wpUrl . '/wp-content/uploads/'. $url;
        }

        $contents = \CR\Utils::curlDownloadFile( $url );
        if ( $contents ) {
            $urlInfo = parse_url( $url );
            $urlPath = $urlInfo[ 'path' ];
            
            if ( $renameSlug ) {
                $fileExt = pathinfo( $urlPath, PATHINFO_EXTENSION );
               // echo "...storing " . $url . " as " . $destPath . '/' . $renameSlug . '.' . $fileExt . "\n";
                file_put_contents( $destPath . '/' . $renameSlug . '.' . $fileExt, $contents );
                return basename( $renameSlug . '.' . $fileExt );
            } else {
                file_put_contents( $destPath . '/' . basename( $urlPath ), $contents );
                return basename( $urlPath );
            }
        } else {
          //  \CR\LOG( "Image not found [" . $origUrl . "]", 3, LOG::WARNING );
        }

        return false;
    }

    private function _contentTypes() {
        return [ 'pages', 'posts' ];;
    }
};