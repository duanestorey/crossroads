<?php

namespace CR;

use ScssPhp\ScssPhp\Compiler;

class SASS {
    static function isSassFile( $filename ) {
        return ( ( strpos( $filename, '.scss' ) !== false ) || ( strpos( $filename, '.sass' ) !== false ) );
    }

    static function parseFile( $filename ) {
        // makes no assumptions about the file
        $sass = false;

        if ( file_exists( $filename ) ) {
            $contents = file_get_contents( $filename );

            $compiler = new Compiler();

            $sass = $compiler->compileString( $contents )->getCss();     
        }

        return $sass;
    }   
}