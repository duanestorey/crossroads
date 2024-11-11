<?php

namespace CR;

class DB {
    var $sql;
    protected $config;

    public function __construct( $config ) {
        $this->config = $config;
        $this->sql = new MYSQL( $config );
    }

    public function rebuild() {
        $this->sql->rebuild();
    }

    public function addContent( $content ) {
        $this->sql->query( "BEGIN" );  

        $queryString = sprintf( 
            'INSERT INTO "content" (type, hash, rel_url, slug, html, title, description, featured, created_at, modified_at, content_slug, markdown, original_html) VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s)',
    $this->sql->escapeWithTicks( $content->contentType ),
            $this->sql->escapeWithTicks( $content->unique ),
            $this->sql->escapeWithTicks( $content->relUrl ),
            $this->sql->escapeWithTicks( $content->slug ),
            $this->sql->escapeWithTicks( $content->html ),
            $this->sql->escapeWithTicks( $content->title ),
            $this->sql->escapeWithTicks( $content->description ),
            $this->sql->escapeWithTicks( $content->featuredImage ),
            $this->sql->escapeWithTicks( date( 'Y-m-d H:i:s', $content->publishDate ) ),
            $this->sql->escapeWithTicks( date( 'Y-m-d H:i:s', $content->modifiedDate ) ),
            $this->sql->escapeWithTicks( $content->contentPath ),
            $this->sql->escapeWithTicks( $content->markdownData ),
            $this->sql->escapeWithTicks( $content->originalHtml )
        );

        LOG( sprintf( "Importing [%s]", $content->slug ), 2, LOG::DEBUG );

        $this->sql->query( $queryString );
        $lastRow = $this->sql->getLastRowID();

        if ( count( $content->taxonomy ) ) {
            foreach( $content->taxonomy as $taxType => $terms ) {
                foreach( $terms as $term ) {
                    $queryString = sprintf( 
                        'INSERT INTO "taxonomy" (type, tax, term, content_id) VALUES (%s, %s, %s, %d)',
                        $this->sql->escapeWithTicks( $content->contentType ),
                        $this->sql->escapeWithTicks( $taxType ),
                        $this->sql->escapeWithTicks( $term ),
                        $this->sql->escape( $lastRow )
                    );

                    LOG( sprintf( "Importing tax/term [%s]", $taxType . '/' . $term ), 2, LOG::DEBUG );

                    $this->sql->query( $queryString );  
                }
            }     
        }


        if ( count( $content->imageInfo ) ) { 
            foreach( $content->imageInfo as $image ) {
                //print_r( $image );
                $this->addImageToDb( $image, $lastRow );;
  
                if ( count( $image->responsiveImages ) ) {
                    foreach( $image->responsiveImages as $size => $respImage ) {
                        $this->addImageToDb( $respImage, $lastRow, $image->url );
                    }
                }
            }
        }

        $this->sql->query( sql: "COMMIT" ); 
    }

    protected function addImageToDb( $image, $id, $respFile = '' ) {
        if ( $image->is_local && $image->isValid ) {
            $queryString = sprintf( 
                'INSERT INTO "images" (filename, width, height, resp_filename, content_id, mod_time) VALUES (%s, %d, %d, %s, %d, %u)',
                $this->sql->escapeWithTicks( $image->url ),
                $this->sql->escape( $image->width ),
                $this->sql->escape( $image->height ),
                $this->sql->escapeWithTicks( $respFile ),
                $this->sql->escape( $id ),
                $this->sql->escape( $image->modificationTime )
            );

            // LOG( "Importing images", 2, LOG::DEBUG );
            $this->sql->query( $queryString ); 
        }
    }

    public function getAllContent() {
        return $this->sql->query( "SELECT * FROM content" );
    }

    public function getContentType( $contentType ) {
        return $this->sql->query( sprintf( "SELECT * FROM content WHERE type = %s", $this->sql->escapeWithTicks( $contentType ) ) );
    }

    public function getAllTaxForContent( $contentId ) {
        return $this->sql->query( sprintf( "SELECT tax,term FROM taxonomy WHERE content_id = %d", $this->sql->escape( $contentId ) ) );
    }

    public function getAllTerms() {
        return $this->sql->query( "SELECT * FROM content" );
    }
}