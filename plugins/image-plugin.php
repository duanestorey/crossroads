<?php

namespace CR;

class ImagePlugin extends Plugin {
    var $config = null;
    var $convert_to_webp = false;
    var $generate_responsive = false;

    public function __construct( $config ) {
        $this->config = $config;

        if ( isset( $this->config[ 'images' ] ) && isset( $this->config[ 'images' ][ 'convert_to_webp' ] ) ) {
            $this->convert_to_webp = $this->config[ 'images' ][ 'convert_to_webp' ];
        }

        if ( isset( $this->config[ 'images' ] ) && isset( $this->config[ 'images' ][ 'generate_responsive' ] ) ) {
            $this->generate_responsive = $this->config[ 'images' ][ 'generate_responsive' ];
        }
    }

    public function content_filter( $content ) {
        $regexp = '(<img[^>]+src=(?:\"|\')\K(.[^">]+?)(?=\"|\'))';
        $image_destination_path = CROSSROAD_PUBLIC_DIR . '/assets/' . $content->content_type;

        if( preg_match_all( "/$regexp/", $content->markdown_html, $matches, PREG_SET_ORDER ) ) {
            //print_r( $matches ); die;
            foreach( $matches as $images ) {
                $image_file = $images[ 0 ];

                $dest_url = $this->_find_and_fix_image( 
                    $image_file,
                    pathinfo( $content->markdown_file, PATHINFO_DIRNAME ),
                    '/assets/' . $content->content_type . '/' . date( 'Y', $content->publish_date ),
                    $content->publish_date,
                    $found_file
                );

                if ( $dest_url && $dest_url->is_local ) {
                    $image_tag = $images[ 1 ];
                    $new_image_tag = str_replace( $image_file, $dest_url->url, $image_tag );

                    // now add srcset
                    if ( $dest_url->has_responsive ) {
                        $srcset = array();

                        foreach( $dest_url->responsive_images as $image ) {
                            $srcset[] = $image->url . ' ' . $image->width . 'w';
                        }

                        $srcset_text = implode( ',', $srcset );

                        $new_image_tag = str_replace( '<img ', '<img loading="lazy" srcset="' . $srcset_text . '" ', $image_tag );
                    }

                     $content->markdown_html = str_replace( $image_tag, $new_image_tag, $content->markdown_html );

                   // print_r( $matches ); die;
                }
            }
        }

        if ( $content->featured_image ) {
            $content->featured_image = $this->_find_and_fix_image( 
                $content->featured_image,
                pathinfo( $content->markdown_file, PATHINFO_DIRNAME ),
                '/assets/' . $content->content_type . '/' . date( 'Y', $content->publish_date ),
                $content->publish_date,
                $found_file
            );
        }

        return $content;
    }    

    public function template_param_filter( $params ) {
        return $params;
    }

    private function _get_image_name_for_responsive( $destination_image, $destination_width ) {
       $image_ext = pathinfo( $destination_image, PATHINFO_EXTENSION );

       return str_replace( '.' . $image_ext, '-' . $destination_width . 'w.' . $image_ext, $destination_image );
    }

    private function _convert_image( $source_image, $destination_image, $force_width = false, $format_conversion = false ) {
        $source_ext = pathinfo( $source_image, PATHINFO_EXTENSION );
        $dest_ext = pathinfo( $destination_image, PATHINFO_EXTENSION );

        $image_data = false;
        $image_size = getimagesize( $source_image );

        if ( $image_size && $force_width && $force_width > $image_size[ 0 ] ) {
            return false;
        }

        switch( $source_ext ) {
            case 'jpg':
            case 'jpeg':
                $image_data = imagecreatefromjpeg( $source_image );
                break;
            case 'gif':
                $image_data = imagecreatefromgif( $source_image );
                break;
            case 'webp';
                $image_data = imagecreatefromwebp( $source_image );
                break;
            case 'png';
                $image_data = imagecreatefrompng( $source_image );
                break;
            default:
                echo "............unknown image format\n";
                break;
        }

        if ( $image_data ) {
            // creating responsive image

            if ( $force_width && $force_width < $image_size[ 0 ] ) {
                // only resample if the image is larger than our target
                $new_width = $force_width;
                $new_height = floor( $force_width * $image_size[ 1 ] / $image_size[ 0 ] );

                $new_image = imagecreatetruecolor( $new_width, $new_height );
                imagecopyresampled( $new_image, $image_data, 0, 0, 0, 0, $new_width, $new_height, $image_size[ 0 ], $image_size[ 1 ] );

                imagedestroy( $image_data );
                $image_data = $new_image;
            } 

            echo "..............writing image [" . $destination_image . "]\n";

            if ( $format_conversion ) {
                imagepalettetotruecolor( $image_data );
                imageavif( $image_data, $destination_image );
            } else {
                switch( $dest_ext ) {
                    case 'jpg':
                    case 'jpeg':
                        imagejpeg( $image_data, $destination_image );
                        break;
                    case 'gif':
                        imagegif( $image_data, $destination_image );
                        break;
                    case 'webp';
                        imagewebp( $image_data, $destination_image );
                        break;
                    case 'png';
                        imagepng( $image_data, $destination_image );
                        break;
                    default:
                        echo "............unknown image format\n";
                        break;                
                }
            }

            imagedestroy( $image_data );
            return true;
        }

        return false;
    }

    private function _convert_or_copy_image( $source_image, $destination_image, $is_primary = true, $force_width = false, $format_conversion = false ) {
        $image_ext = pathinfo( $source_image, PATHINFO_EXTENSION );
        if ( $format_conversion ) {
            // check to see if it's a jpg, otherwise disable conversion   
            if ( $image_ext != 'jpeg' && $image_ext != 'jpg' && $image_ext != 'png' ) {
                $format_conversion = false;
            }
        }

        if ( $force_width || $format_conversion ) {
            if ( $force_width ) {
                if ( $format_conversion ) {
                    $destination_image = str_replace( '.' . $image_ext, '.avif', $destination_image );
                } 

                $destination_image = $this->_get_image_name_for_responsive( $destination_image, $force_width );
            } else if ( $format_conversion ) {
                $destination_image = str_replace( '.' . $image_ext, '.avif', $destination_image );
            }
        }

        // skip if already done
        if ( file_exists( $destination_image ) ) {
            //echo "....skipping image " . $destination_image . "\n";
            return $this->_get_image_information( $destination_image, $is_primary  );
        }

        if ( $force_width || $format_conversion ) {
            // we have to process the image
            if ( $force_width ) {
                if ( $format_conversion ) {
                    echo "............potentially converting image to AVIF and width [" . $force_width . "]\n";
                } else {
                    echo "............potentially converting image to width [" . $force_width . "]\n";
                }
            } else if ( $format_conversion ) {
                echo "............converting image to AVIF\n";
            }       

            $result = $this->_convert_image( $source_image, $destination_image, $force_width, $format_conversion );      

          //  echo "result " . ( $result ? '1' : '0' ) . "\n";

            return ( $result ? $this->_get_image_information( $destination_image, $is_primary ) : $result );
        } else {
            // direct copy
            echo "..........copying image to [" . $destination_image . "]\n";
            Utils::copy_file( $source_image, $destination_image );

            return $this->_get_image_information( $destination_image, $is_primary );
        }
    }

    private function _is_remote_image( $image_file ) {
        if ( strpos( $image_file, 'http://' ) !== false || strpos( $image_file, 'https://' ) !== false ) {
            return true;
        }

        return false;
    }


    private function _get_image_information( $image_file, $include_resp = true, $is_remote = false ) {
        $image_info = new \stdClass;
        $image_info->width = false;
        $image_info->height = false;
        $image_info->responsive_largest_size = 0;

        if ( $is_remote || $this->_is_remote_image( $image_file ) ) {
            $image_info->url = $image_file;
            $image_info->is_local = false;
            $image_info->path = false;

            $image_info->type = false;

            if ( $include_resp ) {
                $image_info->has_responsive = false;
                $image_info->responsive_images = false;
            }
        } else if ( file_exists( $image_file ) ) {
            $image_info = new \stdClass;
            $image_info->is_local = true;
            $image_info->path = $image_file;

            $image_size = getimagesize( $image_file );
            if ( $image_size ) {
                $image_info->width = $image_size[ 0 ];
                $image_info->height = $image_size[ 1 ];
            }

            $image_info->type = pathinfo( $image_file, PATHINFO_EXTENSION );
            $image_info->url = str_replace( CROSSROAD_PUBLIC_DIR, '', $image_file );
            $image_info->public_url = str_replace( CROSSROAD_PUBLIC_DIR, Utils::fix_path( $this->config[ 'site' ][ 'url'] ), $image_file );

            if ( $include_resp ) {
                $image_info->has_responsive = false;
                $image_info->responsive_images = [];
            }

            if ( $image_info->type == 'jpeg' ) {
                $image_info->type = 'jpg';
            }
        } 

        return $image_info;
    }

    private function _find_and_fix_image( $image_file, $current_path, $destination_path, $publish_date, &$found_file, $search_dirs = [ '', 'images/' ] ) {
        if ( $this->_is_remote_image( $image_file ) ) {
            echo "........skipping remote image [" . $image_file . "]\n";
            return $this->_get_image_information( $image_file );
        }

        $found_file = false;
        $image_found = false;
        $new_location = false;

        foreach( $search_dirs as $search_dir ) {
            $original_image_file = $search_dir . $image_file;
            $image_filename_only = pathinfo( $original_image_file, PATHINFO_BASENAME );
            $image_destination_path_with_date = $destination_path;

            echo "........checking image " . $current_path . '/' . $original_image_file . "\n";

            // we have a valid source file
            if ( file_exists( $current_path . '/' . $original_image_file ) ) {  
                @mkdir( CROSSROAD_PUBLIC_DIR . $image_destination_path_with_date, 0755, true );
                $destination_file = CROSSROAD_PUBLIC_DIR . $image_destination_path_with_date . '/' . $image_filename_only;
                $found_file = $current_path . '/' . $original_image_file;

                $main_image = $this->_convert_or_copy_image( $found_file, $destination_file, true, false, $this->convert_to_webp );
                if ( $main_image && $this->generate_responsive ) {

                    $responsive_sizes = [ 320, 480, 640, 960, 1360, 1600 ];

                    foreach( $responsive_sizes as $size ) {
                         $image = $this->_convert_or_copy_image( $found_file, $destination_file, false, $size, $this->convert_to_webp );

                         if ( $image ) {
                            $main_image->responsive_images[ $size ] = $image;
                         }
                    }

                    //print_r( $main_image );

                    $main_image->has_responsive = ( count( $main_image->responsive_images ) > 0 );
                    if ( $main_image->has_responsive ) {
                        $main_image->responsive_largest_size = max( array_keys( $main_image->responsive_images ) );
                    }
                } 

                $new_location = $main_image;
                $image_found = true;

                /*

                if ( !file_exists( $destination_file ) ) {
                    if ( $convert_to_webp ) {
                        echo "........converting image [" . $original_image_file . "] to [" . $destination_file . "]\n";

                        $image = false;
                        if ( $image_ext == 'jpg' || $image_ext == 'jpeg' ) {
                            $image = imagecreatefromjpeg( $current_path . '/' . $original_image_file );
                        } 
                        
                        if ( $image ) {
                            imagewebp( $image, $destination_file, 85 );
                            imagedestroy( $image );
                        }
                    } else {
                        echo "........copying image [" . $image_filename_only . "] to [" . $destination_file . "]\n";
                        Utils::copy_file( $current_path . '/' . $original_image_file, $destination_file );   
                    }             
                }

                $image_info = $this->_get_image_information( $destination_file );
               // print_r( $image_info );
               // die;

                $new_location = $this->config[ 'site' ][ 'url'] . $destination_path . '/' . $image_filename_only;
                */
                break;
            }
        }
  
        return $new_location;
    }    
}