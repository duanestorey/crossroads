<?php
/*
    All code copyright (c) 2024 by Duane Storey - All rights reserved
    You may use, distribute and modify this code under the terms of GPL version 3.0 license or later
*/

namespace CR;

class ImagePlugin extends Plugin {
    var $config = null;
    var $convertToWebp = false;
    var $generateResponsive = false;

    public function __construct( $config ) {   
        parent::__construct( 'image' );

        $this->config = $config;

        $this->convertToWebp = $config->get( 'images.convert_to_webp' );
        $this->generateResponsive = $config->get( 'images.generate_responsive' ); 
    }

    public function processOne( $entry ) {
        $entry = $this->contentFilter( $entry );

        return $entry;
    }

    public function contentFilter( $content ) {
        $regexp = '(<img[^>]+src=(?:\"|\')\K(.[^">]+?)(?=\"|\'))';
        $imageDestinationPath = CROSSROADS_PUBLIC_DIR . '/assets/' . $content->contentType;

        if( preg_match_all( "/$regexp/", $content->html, $matches, PREG_SET_ORDER ) ) {

            foreach( $matches as $images ) {
                $imageFile = $images[ 0 ];

                $destUrl = $this->_findAndFixImage( 
                    $imageFile,
                    pathinfo( $content->markdownFile, PATHINFO_DIRNAME ),
                    '/assets/' . $content->contentType . '/' . date( 'Y', $content->publishDate ),
                    $content->publishDate,
                    $foundFile
                );

                if ( $destUrl && $destUrl->is_local ) {
                    $image_tag = $images[ 1 ];
                    $newImageTag = str_replace( $imageFile, $destUrl->url, $image_tag );

                    // now add srcset
                    if ( $destUrl->hasResponsive ) {
                        $srcset = array();

                        foreach( $destUrl->responsiveImages as $image ) {
                            $srcset[] = $image->url . ' ' . $image->width . 'w';
                        }

                        $srcset_text = implode( ',', $srcset );

                        $newImageTag = str_replace( '<img ', '<img loading="lazy" srcset="' . $srcset_text . '" ', $image_tag );
                    }

                    $content->html = str_replace( $image_tag, $newImageTag, $content->html );
                }
            }
        }

        if ( $content->featuredImage ) {
            $content->featuredImageData = $this->_findAndFixImage( 
                $content->featuredImage,
                pathinfo( $content->markdownFile, PATHINFO_DIRNAME ),
                '/assets/' . $content->contentType . '/' . date( 'Y', $content->publishDate ),
                $content->publishDate,
                $foundFile
            );
        }

        return $content;
    }    

    public function templateParamFilter( $params ) {
        return $params;
    }

    private function _getImageNameForResponsive( $destinationImage, $destinationWidth ) {
       $imageExt = pathinfo( $destinationImage, PATHINFO_EXTENSION );

       return str_replace( '.' . $imageExt, '-' . $destinationWidth . 'w.' . $imageExt, $destinationImage );
    }

    private function _convert_image( $sourceImage, $destinationImage, $forceWidth = false, $formatConversion = false ) {
        $sourceExt = pathinfo( $sourceImage, PATHINFO_EXTENSION );
        $destExt = pathinfo( $destinationImage, PATHINFO_EXTENSION );

        $imageData = false;
        $imageSize = getimagesize( $sourceImage );

        if ( $imageSize && $forceWidth && $forceWidth > $imageSize[ 0 ] ) {
            return false;
        }

        switch( $sourceExt ) {
            case 'jpg':
            case 'jpeg':
                $imageData = @imagecreatefromjpeg( $sourceImage );
                break;
            case 'gif':
                $imageData = @imagecreatefromgif( $sourceImage );
                break;
            case 'webp';
                $imageData = @imagecreatefromwebp( $sourceImage );
                break;
            case 'png';
                $imageData = @imagecreatefrompng( $sourceImage );
                break;
            default:
                break;
        }

        if ( $imageData ) {
            // creating responsive image

            if ( $forceWidth && $forceWidth < $imageSize[ 0 ] ) {
                // only resample if the image is larger than our target
                $newWidth = $forceWidth;
                $newHeight = floor( $forceWidth * $imageSize[ 1 ] / $imageSize[ 0 ] );

                $newImage = imagecreatetruecolor( $newWidth, $newHeight );
                imagecopyresampled( $newImage, $imageData, 0, 0, 0, 0, $newWidth, $newHeight, $imageSize[ 0 ], $imageSize[ 1 ] );

                imagedestroy( $imageData );
                $imageData = $newImage;
            } 

            LOG( "Writing new image [" . $destinationImage . "]", 2, LOG::DEBUG );

            if ( $formatConversion ) {
                imagepalettetotruecolor( $imageData );
                imageavif( $imageData, $destinationImage );
            } else {
                switch( $destExt ) {
                    case 'jpg':
                    case 'jpeg':
                        imagejpeg( $imageData, $destinationImage );
                        break;
                    case 'gif':
                        imagegif( $imageData, $destinationImage );
                        break;
                    case 'webp';
                        imagewebp( $imageData, $destinationImage );
                        break;
                    case 'png';
                        imagepng( $imageData, $destinationImage );
                        break;
                    default:
                        echo "............unknown image format writing\n";
                        break;                
                }
            }

            imagedestroy( $imageData );
            return true;
        } else {
            LOG( "Unable to load image [" . $sourceImage . "]", 2, LOG::WARNING );
            return false;
        }

        return false;
    }

    private function _convertOrCopyImage( $sourceImage, $destinationImage, $isPrimary = true, $forceWidth = false, $formatConversion = false ) {
        $imageExt = pathinfo( $sourceImage, PATHINFO_EXTENSION );
        if ( $formatConversion ) {
            // check to see if it's a jpg, otherwise disable conversion   
            if ( $imageExt != 'jpeg' && $imageExt != 'jpg' && $imageExt != 'png' ) {
                $formatConversion = false;
            }
        }

        if ( $forceWidth || $formatConversion ) {
            if ( $forceWidth ) {
                if ( $formatConversion ) {
                    $destinationImage = str_replace( '.' . $imageExt, '.avif', $destinationImage );
                } 

                $destinationImage = $this->_getImageNameForResponsive( $destinationImage, $forceWidth );
            } else if ( $formatConversion ) {
                $destinationImage = str_replace( '.' . $imageExt, '.avif', $destinationImage );
            }
        }

        // skip if already done
        if ( file_exists( $destinationImage ) ) {
            ///echo "....skipping image " . $destinationImage . "\n";
            return $this->_getImageInformation( $destinationImage, $isPrimary );
        }

        if ( $forceWidth || $formatConversion ) {
            // we have to process the image
            if ( $forceWidth ) {
                if ( $formatConversion ) {
                    LOG( "Potentially converting image to AVIF and width [" . $forceWidth . "]", 4, LOG::DEBUG );
                } else {
                    LOG( "Potentially converting image to width [" . $forceWidth . "]", 4, LOG::DEBUG );
                }
            } else if ( $formatConversion ) {
                echo "............converting image to AVIF\n";
            }       

            $result = $this->_convert_image( $sourceImage, $destinationImage, $forceWidth, $formatConversion );     

            if ( $result !== false ) {
                return $this->_getImageInformation( $destinationImage, $isPrimary );
            } else {
                return false;
            }
        } else {
            // direct copy
            LOG( "Directly copying image to [" . $destinationImage . "]", 4, LOG::DEBUG );
            Utils::copyFile( $sourceImage, $destinationImage );

            return $this->_getImageInformation( $destinationImage, $isPrimary );
        }
    }

    private function _isValidImage( $imageFile ) {
        $a = getimagesize($imageFile);
        if ( $a ) {
           $image_type = $a[2];

            if ( in_array( $image_type , array( IMAGETYPE_GIF, IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_WEBP  ) ) ) {
                return true;
            }     
        }
    
        return false;   
    }

    private function _isRemoteImage( $imageFile ) {
        if ( strpos( $imageFile, 'http://' ) !== false || strpos( $imageFile, 'https://' ) !== false ) {
            return true;
        }

        return false;
    }


    private function _getImageInformation( $imageFile, $includeResp = true, $isRemote = false ) {
        $imageInfo = new \stdClass;
        $imageInfo->width = false;
        $imageInfo->height = false;
        $imageInfo->responsive_largest_size = 0;

        if ( $isRemote || $this->_isRemoteImage( $imageFile ) ) {
            $imageInfo->url = $imageFile;
            $imageInfo->is_local = false;
            $imageInfo->path = false;

            $imageInfo->type = false;

            if ( $includeResp ) {
                $imageInfo->hasResponsive = false;
                $imageInfo->responsiveImages = false;
            }
        } else if ( file_exists( $imageFile ) ) {
            $imageInfo = new \stdClass;
            $imageInfo->is_local = true;
            $imageInfo->path = $imageFile;
            $imageInfo->isValid = $this->_isValidImage( $imageFile );

            $imageSize = getimagesize( $imageFile );
            if ( $imageSize ) {
                $imageInfo->width = $imageSize[ 0 ];
                $imageInfo->height = $imageSize[ 1 ];
            }

            $imageInfo->type = pathinfo( $imageFile, PATHINFO_EXTENSION );
            $imageInfo->url = str_replace( CROSSROADS_PUBLIC_DIR, '', $imageFile );
            $imageInfo->public_url = str_replace( CROSSROADS_PUBLIC_DIR, Utils::fixPath( $this->config->get( 'site.url' ) ), $imageFile );

            if ( $includeResp ) {
                $imageInfo->hasResponsive = false;
                $imageInfo->responsiveImages = [];
            }

            if ( $imageInfo->type == 'jpeg' ) {
                $imageInfo->type = 'jpg';
            }
        } 

        return $imageInfo;
    }

    private function _findAndFixImage( $imageFile, $currentPath, $destinationPath, $publishDate, &$foundFile, $search_dirs = [ '', 'images/' ] ) {
        if ( $this->_isRemoteImage( $imageFile ) ) {
            LOG( "........skipping remote image [" . $imageFile . "]", 3, LOG::DEBUG );
            return $this->_getImageInformation( $imageFile );
        }

        $foundFile = false;
        $imageFound = false;
        $newLocation = false;

        foreach( $search_dirs as $search_dir ) {
            $originalImageFile = $search_dir . $imageFile;
            $imageFilename_only = pathinfo( $originalImageFile, PATHINFO_BASENAME );
            $imageDestinationPathWithDate = $destinationPath;

            LOG( "Checking image " . $currentPath . '/' . $originalImageFile, 3, LOG::DEBUG );

            // we have a valid source file
            if ( file_exists( $currentPath . '/' . $originalImageFile ) ) {  
                @mkdir( CROSSROADS_PUBLIC_DIR . $imageDestinationPathWithDate, 0755, true );
                $destinationFile = CROSSROADS_PUBLIC_DIR . $imageDestinationPathWithDate . '/' . $imageFilename_only;
                $foundFile = $currentPath . '/' . $originalImageFile;

                $mainImage = $this->_convertOrCopyImage( $foundFile, $destinationFile, true, false, $this->convertToWebp );
                if ( $mainImage && $this->generateResponsive ) {
                    if ( $mainImage->is_local && $mainImage->isValid ) {
                        $responsiveSizes = [ 320, 480, 640, 960, 1360, 1600 ];

                        foreach( $responsiveSizes as $size ) {
                            $image = $this->_convertOrCopyImage( $foundFile, $destinationFile, false, $size, $this->convertToWebp );

                            if ( $image ) {
                                $mainImage->responsiveImages[ $size ] = $image;
                            }
                        }

                        $mainImage->hasResponsive = ( count( $mainImage->responsiveImages ) > 0 );
                        if ( $mainImage->hasResponsive ) {
                            $mainImage->responsive_largest_size = max( array_keys( $mainImage->responsiveImages ) );
                        }
                    } else {
                        LOG( sprintf( _i18n( 'plugins.image.corrupt' ), $mainImage->url ), 3, LOG::WARNING );
                    }
                } 

                $newLocation = $mainImage;
                $imageFound = true;

                break;
            }
        }
  
        return $newLocation;
    }    
}