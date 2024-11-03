<?php

namespace CR;

//use Symfony\Component\Yaml\Yaml;
//use Symfony\Component\Yaml\Exception\ParseException;

class YAML {
    static function parse_file( $path_to_file ) {
        $value = false;

        try {
            $value = \Symfony\Component\Yaml\Yaml::parseFile( $path_to_file );
        } catch( \Symfony\Component\Yaml\Exception\ParseException $exception ) {
            // do somethign here
        }
        
        return $value;
    }

    static function parse( $data ) {
        $value = false;

        try {
            $value = \Symfony\Component\Yaml\Yaml::parse( $data );
        } catch( \Symfony\Component\Yaml\Exception\ParseException $exception ) {
            // do somethign here
        }
        
        return $value;    
    }
}