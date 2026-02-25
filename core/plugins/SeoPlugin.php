<?php

namespace CR\Plugins;

use CR\Plugin;

class SeoPlugin extends Plugin
{
    public $config = null;

    public function __construct($config)
    {
        parent::__construct('seo');

        $this->config = $config;
    }

    public function processOne($content)
    {
        return $content;
    }
}
