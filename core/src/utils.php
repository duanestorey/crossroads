<?php

namespace CR;

class Utils {
    static function fixPath( $dir ) {
        return rtrim( $dir, "\\/" );
    }

    static function copyFile( $source, $dest ) {
        copy( $source, $dest );
    }

    static function mkdir( $dirname ) {
        if ( !file_exists( $dirname ) ) {
            @mkdir( $dirname );
        }
    }

    static function titleToSlug( $title ) {
        return preg_replace( '/[^a-zA-Z0-9-]/', '', str_replace( array( ' ', '_', '-' ), '-', strtolower( $title ) ) );
    }

    static function recursiveRmdir( $directory ) {
        $files = array_diff( scandir( $directory ), array( '.', '..' ) );
        if ( count( $files ) ) {
            foreach( $files as $file ) {
                $cur_location = $directory . '/' . $file;

                if ( is_dir( $cur_location ) ) {
                    Utils::recursiveRmdir( $cur_location );
                } else {
                   unlink( $cur_location );
                }
            } 
        }

        rmdir( $directory );
    }

    static function curlDownloadFile( $url ) {
        $result = false;

        $ch = curl_init( $url );
        if ( $ch ) {
            curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
            curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, 0 );
            curl_setopt( $ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC );
            curl_setopt( $ch, CURLOPT_CONNECTTIMEOUT, 10 ) ;
            curl_setopt( $ch, CURLOPT_TIMEOUT, 20 );
            curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, true );

            $result = curl_exec($ch);

            if ( $result !== false ) {
                $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                if ( $status != 200 ) {
                    $result = false;
                }
            }

            curl_close($ch);
        }
   

        return $result;
    }

    static function cleanTerm( $term ) {
        return strtolower( str_replace( ' ', '-', $term ) );
    }

    static function findAllFilesWithExtension( $directory, $ext ) {
        $allFiles = array();
        if ( !is_array( $ext ) ) {
            $ext = [ $ext ];
        }

        if ( !file_exists( $directory ) ) {
            return $allFiles;
        }

        $filenames = array_diff( scandir( $directory ), array( '.', '..' ) );
        foreach( $filenames as $one_file ) {
            $full_path = $directory . '/' . $one_file;
            if ( is_dir( $full_path ) ) {
                $allFiles = array_merge( $allFiles, Utils::findAllFilesWithExtension( $full_path, $ext ) );
            } else if ( in_array( pathinfo( $full_path, PATHINFO_EXTENSION ), $ext ) ) {
                $allFiles[] = $full_path;
            }
        }

        return $allFiles;
    }    
}