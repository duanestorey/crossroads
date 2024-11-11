<?php

namespace CR;

class LatteFileLoader extends \Latte\Loaders\FileLoader {
    protected $templateDirs = [];

    public function setDirectories( $templateDirs ) {
        $this->templateDirs = $templateDirs;
    }

    public function getContent( string $fileName ): string {
        foreach( $this->templateDirs as $dir ) {
            $pathToFile = $dir . '/' . $fileName;

            if ( file_exists( $pathToFile ) ) {
                return file_get_contents( $pathToFile );
            }
        }

        // not found
        throw new Latte\RuntimeException( "Missing template file '$fileName'." );

        return false;
	}
}