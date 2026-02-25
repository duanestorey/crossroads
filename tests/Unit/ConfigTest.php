<?php

use CR\Config;

it('returns a value for a known key', function () {
    $config = new Config(['site.name' => 'Test Site']);
    expect($config->get('site.name'))->toBe('Test Site');
});

it('returns default when key is missing', function () {
    $config = new Config(['site.name' => 'Test Site']);
    expect($config->get('site.missing', 'fallback'))->toBe('fallback');
});

it('returns false as default when no default specified', function () {
    $config = new Config(['site.name' => 'Test Site']);
    expect($config->get('site.missing'))->toBeFalse();
});

it('handles null config gracefully', function () {
    $config = new Config(null);
    expect($config->get('anything', 'default'))->toBe('default');
});

it('returns array values', function () {
    $config = new Config(['content.posts.taxonomy' => ['categories', 'tags']]);
    expect($config->get('content.posts.taxonomy'))->toBe(['categories', 'tags']);
});
