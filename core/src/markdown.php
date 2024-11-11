<?php

namespace CR;

class Markdown {
    var $frontMatter = false;
    var $markdown = false;

    public function __construct() {}

    public function loadFile( $filename ) {
        $contents = file_get_contents( $filename );

        if ( $contents ) {
            // find front matter
            $front = $this->_getfrontMatter( $contents );
            if ( $front ) {
                // Strip front matter from markdown
                $this->markdown = trim( str_replace( $front[ 0 ], '', $contents ) );
                $this->frontMatter = YAML::parse( trim( $front[ 1 ] ) );
            } else {
                $this->markdown = $contents;
            }        
        }

        return ( $contents !== false );
    }

    public function frontMatter() {
        return $this->frontMatter;
    }

    public function rawMarkdown() {
        return $this->markdown;
    }

    public function strippedMarkdown() {
        return strip_tags( $this->markdown );
    }

    public function html() {
        $parsedown = new \Parsedown();
        return $parsedown->text( $this->markdown );
    }

    private function _getfrontMatter( &$contents ) {
        if ( preg_match( '/---(.*)---/iUs', $contents, $matches ) ) {
            return $matches;
        } else {
            return false;
        }
    }
}