<?php

namespace CR;

class Entries {
    var $config = null;
    var $entries = array();
    var $tax = array();
    var $totalEntries = 0;
    protected $db = null;
    protected $pluginManager = null;

    public function __construct( $config, $db, $pluginManager ) {
        $this->config = $config;
        $this->db = $db;
        $this->pluginManager = $pluginManager;
    }

    public function getEntryCount() {
        return $this->totalEntries;
    }

    public function get( $contentType ) {
        if ( isset( $this->entries[ $contentType ] ) ) {
            return $this->entries[ $contentType ];
        } 

        return false;
    }

    function getTaxTypes( $contentType ) {
        if ( isset( $this->tax[ $contentType ] ) ) {
            $values = array_keys( $this->tax[ $contentType ] );
            sort( $values );
            return $values;
        }

        return false;
    }

    function getTaxTerms( $contentType, $taxType ) {
        if ( isset( $this->tax[ $contentType ][ $taxType ] ) ) {
            $values = array_keys( $this->tax[ $contentType ][ $taxType ]);
            sort( $values );
            return $values;
        }

        return false;
    }

    function getTax( $contentType, $taxType, $term ) {
        if ( isset( $this->tax[ $contentType ] ) && isset( $this->tax[ $contentType ][ $taxType ] ) && isset( $this->tax[ $contentType ][ $taxType ][ $term ] ) )  {
            return $this->tax[ $contentType ][ $taxType ][ $term ];
        }

        return false;
    }

    public function getAll() {
        $allEntries = array();

        foreach( $this->config->get( 'content', [] ) as $contentType => $contentConfig ) {
            if ( isset( $this->entries[ $contentType ] ) ) {
                $allEntries = array_merge( $allEntries, $this->entries[ $contentType ] );
            }
        }

        return $allEntries;
    }

    public function loadAllDb() {
        $imageProcessor = new ImageProcessor( $this->config );
        foreach( $this->config->get( 'content', [] ) as $contentType => $contentConfig ) {
            if ( !isset( $this->entries[ $contentType ] ) ) {
                $this->entries[ $contentType ] = [];
                $this->tax[ $contentType ] = [];
            }   

            LOG( sprintf( _i18n( 'core.class.entries.processing.loading' ), $contentType ), 1, LOG::INFO );

            $result = $this->db->getContentType( $contentType );
            while ( $row = $result->fetchArray( SQLITE3_ASSOC ) ) {
                $content = new Content( $this->config, $contentType, $contentConfig );
                $content->slug = $row[ 'slug' ];
                $content->title = $row[ 'title' ];
                $content->description = $row[ 'description' ];
                $content->unique = $row[ 'hash' ];
                $content->html = $row[ 'html' ];
                $content->featuredImage = $row[ 'featured' ];
                $content->publishDate = strtotime( $row[ 'created_at' ] );
                $content->modifiedDate = strtotime( $row[ 'modified_at' ] );

                $content->markdownFile = CROSSROADS_CONTENT_DIR . '/' . $row[ 'content_slug' ];

                $content->contentPath = $content->contentType . '/' . $content->slug;
                $content->unique = md5( $content->contentType . '/' . $content->slug ); 
                $content->className = $content->slug;
                
                $tax = $this->db->getAllTaxForContent( $row[ 'id' ] );
                while ( $taxRow = $tax->fetchArray( SQLITE3_ASSOC ) ) {
                    $content->taxonomy[ $taxRow[ 'tax' ] ][] = $taxRow[ 'term' ];
                }

                $content->calculate();

                if ( $content->featuredImage ) {
                    $content->featuredImageData = $imageProcessor->processImage( $content, $content->featuredImage );
                }

                if ( count( $content->taxonomy ) ) {
                    foreach( $content->taxonomy as $tax => $terms ) {
                        foreach( $terms as $term ) {
                            $this->tax[ $contentType ][ $tax ][ $term ][] = $content;
                        }
                    }
                }    
               
                $this->entries[ $contentType ][] = $content;
                $this->totalEntries++;
            }
        }
    }

    public function loadAll() {
        foreach( $this->config->get( 'content', [] ) as $contentType => $contentConfig ) {
            if ( !isset( $this->entries[ $contentType ] ) ) {
                $this->entries[ $contentType ] = [];
                $this->tax[ $contentType ] = [];
            }

            LOG( sprintf( _i18n( 'core.class.entries.processing.loading' ), $contentType ), 1, LOG::INFO );

            $content_directory = \CROSSROADS_CONTENT_DIR . '/' . $contentType;

            $allMarkdownFiles = $this->_findMarkdownFiles( $content_directory );
            if ( is_array( $allMarkdownFiles ) && count( $allMarkdownFiles ) ) {
                foreach( $allMarkdownFiles as $markdownFile ) {
                    LOG( sprintf( _i18n( 'core.class.entries.processing.content' ), pathinfo( $markdownFile, PATHINFO_FILENAME ) ), 2, LOG::DEBUG );

                    $markdown = new Markdown();
                    if ( $markdown->loadFile( $markdownFile ) ) {    
                        $content = new Content( $this->config, $contentType, $contentConfig );
                        $content->slug = $this->getSlugFromName( pathinfo( $markdownFile, PATHINFO_FILENAME ) );
                        $content->markdownFile = $markdownFile;
                        $content->markdownData = $markdown->rawMarkdown();
                        $content->html = $markdown->html();
                        $content->modifiedDate = filemtime( $markdownFile );
                        $content->contentPath = $content->contentType . '/' . basename( $content->markdownFile );
                        $content->modifiedDate = filemtime( $content->markdownFile );
                        $content->unique = md5( basename( $content->markdownFile ) ); 

                        if ( $front = $markdown->frontMatter() ) {
                            $content->title = $this->_findDataInFrontMatter( [ 'title' ], $front,$content->title );
                            $content->slug = $this->_findDataInFrontMatter( [ 'slug' ], $front,$content->slug );
                            $content->publishDate = strtotime( $this->_findDataInFrontMatter( [ 'date', 'publishDate' ], $front, date( 'Y-m-d' ) ) );
                            $content->featuredImage = $this->_findDataInFrontMatter( [ 'featuredImage', 'coverImage', 'heroImage' ], $front, $content->featuredImage );
                            $content->description = $this->_findDataInFrontMatter( [ 'description' ], $front, $content->description );

                            if ( isset( $contentConfig[ 'taxonomy' ] ) ) {
                                foreach( $contentConfig[ 'taxonomy' ] as $tax => $variations ) {
                                    $content->taxonomy[ $tax ] = $this->_findDataInFrontMatter( $variations, $front, [] ); 
                                    $content->taxonomy[ $tax ] = array_map( function( $e ) { return Utils::cleanTerm( $e ); }, $content->taxonomy[ $tax ] );
                                }
                            }

                            // print_r( $content->taxonomy );
                        }

                        $content->originalHtml = $content->html;

                        $content->calculate();
                        $content->processImages();

                        if ( !$content->description ) {
                            $content->description = $content->excerpt( 120, false );  
                        }    

                        if ( count( $content->taxonomy ) ) {
                            foreach( $content->taxonomy as $tax => $terms ) {
                                foreach( $terms as $term ) {
                                    $this->tax[ $contentType ][ $tax ][ $term ][] = $content;
                                }
                            }
                        }

                        $this->entries[ $contentType ][] = $content;
                        $this->totalEntries++;
                    }
                }
            }
            
            $this->entries[ $contentType ] = $this->pluginManager->processAll( $this->entries[ $contentType ] );
        }
    }

    private function getSlugFromName( $name ) {
        if ( preg_match( '#(\d+-\d+-\d+)-(.*)#', $name, $match ) ) {
            $name = $match[ 2 ];
        }
        return $name;
    }

    private function _findDataInFrontMatter( $fields, $frontMatter, $default ) {
        foreach( $fields as $field ) {
            if ( isset( $frontMatter[ $field ] ) ) {
                return $frontMatter[ $field ];
            }
        }

        return $default;
    }

    private function _findMarkdownFiles( $directory ) {
        return Utils::findAllFilesWithExtension( $directory, 'md' );
    }    
}