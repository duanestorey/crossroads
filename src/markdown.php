<?php

namespace CR;

class Markdown {
    var $front_matter = false;
    var $markdown = false;

    public function __construct() {}
    public function load_file( $filename ) {
        $contents = file_get_contents( $filename );

        if ( $contents ) {
            // find front matter
            $front = $this->_get_front_matter( $contents );
            if ( $front ) {
                // Strip front matter from markdown
                $this->markdown = str_replace( $front[ 0 ], '', $contents );
                $this->front_matter = YAML::parse( trim( $front[ 1 ] ) );
            } else {
                $this->markdown = $contents;
            }        
        }

        return ( $contents !== false );
    }

    public function front_matter() {
        return $this->front_matter;
    }

    public function raw_markdown() {
        return $this->markdown;
    }

    public function html() {
        $parsedown = new \Parsedown();
        return $parsedown->text( $this->markdown );
    }

    private function _get_front_matter( &$contents ) {
        if ( preg_match( '/---(.*)---/iUs', $contents, $matches ) ) {
            return $matches;
        } else {
            return false;
        }
    }
}