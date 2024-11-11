<?php

namespace CR;

function cr_sort( $a, $b ) {
    if ( $a->publishDate == $b->publishDate ) {
        return 0;
    }

    return ( $b->publishDate < $a->publishDate ) ? -1 : 1;
}

class Content {
    // configuration data
    public $config = null;
    public $contentConfig = null;
    public $contentType = null;

    public $title = '';
    public $originalTitle = '';
    public $publishDate = 0;
    public $modifiedDate = 0;
    public $url = '';
    public $markdownFile = '';
    public $markdownData = '';
    public $html = '';
    public $originalHtml = '';
    public $featuredImage = false;
    public $featuredImageData = null;
    public $description = '';
    public $slug = '';
    public $taxonomy = [];

    // Calculated fields
    public $taxonomyLinks = [];
    public $className = '';
    public $readingTime = '';
    public $words = '';
    public $relUrl = '';
    public $unique = '';
    public $modifiedHash = '';
    public $imageInfo = [];
    public $contentPath = '';

    public function __construct( $config, $contentType, $contentConfig ) {
        $this->config = $config;
        $this->contentConfig = $contentConfig;
        $this->contentType = $contentType;

        $this->publishDate = time();
    }

    public function calculate() {
        $this->words = str_word_count( strip_tags( $this->html ) );
        $minutes = intdiv( $this->words, 225 );
        if ( $minutes <= 1 ) {
            $this->readingTime = _i18n( 'core.class.entries.reading_time.s' );
        } else {
            $this->readingTime = sprintf( _i18n( 'core.class.entries.reading_time.p' ), $minutes );
        }   

        if ( isset( $this->contentConfig[ 'base' ] ) ) {
            $contentLink =  Utils::fixPath( $this->contentConfig[ 'base' ] ) . '/' . $this->slug . '.html';
        } else {
            $contentLink = '/' . $this->contentType . '/' . $this->slug . '.html';
        }
        
        $this->url = Utils::fixPath( $this->config->get( 'site.url' ) ) . $contentLink;
        $this->relUrl = $contentLink;  
                        
        if ( count( $this->taxonomy ) ) {
            foreach( $this->taxonomy as $tax => $terms ) {
                foreach( $terms as $term ) {
                    $this->taxonomyLinks[ $tax ][ $term ] = '/' . $this->contentType . '/' . $tax . '/' . $term;
                }
            }
        }        
    }

    public function processImages() {
        $imageProcessor = new ImageProcessor( $this->config );

        $allImages = [];
        $allProcessedImages = [];

        if ( $this->featuredImage ) {
            //$allImages[ 'featured' ] = $this->featuredImage;
            $imageInfo = $imageProcessor->processImage( $this, $this->featuredImage );
            if ( $imageInfo ) {
                $this->featuredImageData = $imageInfo;
            }
        }

        $regexp = '(<img[^>]+src=(?:\"|\')\K(.[^">]+?)(?=\"|\'))';
        $imageDestinationPath = CROSSROADS_PUBLIC_DIR . '/assets/' . $this->contentType;

        if( preg_match_all( "/$regexp/", $this->originalHtml, $matches, PREG_SET_ORDER ) ) {
            foreach( $matches as $images ) {
                 $imageFile = $images[ 0 ];

                 $allImages[ $images[ 1 ] ] = $imageFile;
            }
        }

        // we have a list of all images here 
        $toFind = [];
        $toReplace = [];

        foreach( $allImages as $originalTag => $image ) {
            $imageInfo = $imageProcessor->processImage( $this, $image );
            if ( $imageInfo ) {
                 $allProcessedImages[] = $imageInfo;

                if ( $imageInfo->is_local ) {
                    $newImageHtml = str_replace( $image, $imageInfo->url, $originalTag );

                    if ( $imageInfo->hasResponsive ) {
                        $srcset = array();

                        foreach( $imageInfo->responsiveImages as $image ) {
                            $srcset[] = $image->url . ' ' . $image->width . 'w';
                        }

                        $srcset_text = implode( ',', $srcset );

                        $newImageHtml = str_replace( '<img ', '<img loading="lazy" srcset="' . $srcset_text . '" ', $newImageHtml );
                    }

                    $toFind[] = $originalTag;
                    $toReplace[] = $newImageHtml;
                }
            } else {
                
            }
        }

        $this->html = str_replace( $toFind, $toReplace, $this->html );

        $this->imageInfo = $allProcessedImages;
    }

    public function setTitle( $title ) {
        $this->title = $title;
    }

    public function excerpt( $length = 600, $includeEllipsis = true ) {
        $str = '';
        $words = explode( ' ', strip_tags( $this->html ) );

        $len = 0;
        for ( $i = 0; $i < count( $words ); $i++ ) {
            $str = $str . $words[ $i ] . ' ';
            $len += strlen( $words[ $i ] ) + 1;

            if ( $len >= $length ) {
                break;
            }
        }
    
        if ( $includeEllipsis ) {
            $str = $str . '...';
        }

        return rtrim( $str );
    }
}