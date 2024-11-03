<?php

namespace CR;

class TemplateEngine {
    var $latte = null;
    var $template_dir = '.';

    public function __construct() {
        $this->latte = new \Latte\Engine;
        $this->latte->setTempDirectory( sys_get_temp_dir() );
    }

    public function set_template_dir( $template_dir ) {
        $this->template_dir = rtrim( $template_dir, '/' );
    }

    public function template_exists( $template_name ) {
        return file_exists( $this->template_dir . '/' . $template_name . '.latte' );
    }

    public function locate_template( $templates ) {
        foreach( $templates as $template ) {
            if ( $this->template_exists( $template ) ) {
                  return $template;
            }
        }

        return false;
    }

    public function render( $template_file, $params ) {
        if ( $this->latte ) {
            return $this->latte->renderToString( $this->template_dir . '/' . $template_file . '.latte', $params );
        }
    }
}