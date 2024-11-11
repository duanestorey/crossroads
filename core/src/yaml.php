<?php

namespace CR;

class YAML {
    static function parse_file( $path_to_file, $flatten = false ) {
        $value = false;

        try {
            $value = \Symfony\Component\Yaml\Yaml::parseFile( $path_to_file );
        } catch( \Symfony\Component\Yaml\Exception\ParseException $exception ) {
            // do something here
        }

        if ( $flatten ) {
            return YAML::flatten( $value );
        }
        
        return $value;
    }

    static function flatten( $yaml_data, $base_string = '' ) {
        $flattened = [];

        if ( count( $yaml_data ) ) {
            foreach( $yaml_data as $key => $data ) {
                if ( $base_string ) {
                    $new_string = $base_string . '.' . $key;
                } else {
                    $new_string = $key;
                }
                
                if ( is_array( $data ) ) {
                    $flattened[ $new_string ] = $data;
                    $flattened = array_merge( $flattened, YAML::flatten( $data, $new_string ) ); 
                } else {
                    $flattened[ $new_string ] = $data;
                }
            }
        }

        ksort( $flattened );
        return $flattened;
    }

    static function parse( $data ) {
        $value = false;

        try {
            $value = \Symfony\Component\Yaml\Yaml::parse( $data );
        } catch( \Symfony\Component\Yaml\Exception\ParseException $exception ) {
            // do something here
        }
        
        return $value;    
    }
}