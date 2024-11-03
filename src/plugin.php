<?php

namespace CR;

abstract class Plugin {
    abstract public function content_filter( $content );
    abstract public function template_param_filter( $params );
}