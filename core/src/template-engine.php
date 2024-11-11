<?php

namespace CR;

class TemplateEngine {
    var $latte = null;
    var $templateDirs = '.';
    var $config = null;
    
    protected $fileLoader = null;

    public function __construct( $config ) {
        $this->config = $config;

        $this->latte = new \Latte\Engine;
        $this->latte->setLocale( $config->get( 'site.lang', 'en' ) );

        $this->latte->setTempDirectory( sys_get_temp_dir() );
        $this->fileLoader = new LatteFileLoader();
        $this->latte->setLoader( $this->fileLoader );
    }

    public function setTemplateDirs( $templateDirs ) {
        $this->templateDirs = $templateDirs;

        $this->fileLoader->setDirectories( $templateDirs );
    }

    public function templateExists( $templateName ) {
        foreach( $this->templateDirs as $dir ) {
            if ( file_exists( $dir . '/' . $templateName . '.latte' ) ) {
                return true;
            }
        }

        LOG( sprintf( "Template file doesn't exist [%s]", $templateName ), 2, LOG::WARNING );

        return false;
    }

    public function locateTemplate( $templates ) {
        if ( !is_array( $templates ) ) {
            $templates = array( $templates );
        }

        foreach( $templates as $template ) {
            if ( $this->templateExists( $template ) ) {
                  return $template;
            }
        }

        return false;
    }

    public function render( $templateFile, $params ) {
        if ( $this->latte ) {
            return $this->latte->renderToString( $templateFile . '.latte', $params );
        }
    }
}