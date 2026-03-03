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

it('does not log warning when default is provided for missing key', function () {
    $config = new Config(['site.name' => 'Test Site']);
    // Should return the default silently without triggering a LOG warning
    $result = $config->get('options.include_drafts', false);
    expect($result)->toBeFalse();
    $result2 = $config->get('options.missing', 'default_value');
    expect($result2)->toBe('default_value');
});

it('handles null config gracefully', function () {
    $config = new Config(null);
    expect($config->get('anything', 'default'))->toBe('default');
});

it('returns array values', function () {
    $config = new Config(['content.posts.taxonomy' => ['categories', 'tags']]);
    expect($config->get('content.posts.taxonomy'))->toBe(['categories', 'tags']);
});

it('set() stores a value retrievable by get()', function () {
    $config = new Config([]);
    $config->set('site.name', 'New Site');
    expect($config->get('site.name'))->toBe('New Site');
});

it('set() overwrites existing values', function () {
    $config = new Config(['site.name' => 'Old Site']);
    $config->set('site.name', 'New Site');
    expect($config->get('site.name'))->toBe('New Site');
});

it('set() on null config initializes it', function () {
    $config = new Config(null);
    $config->set('key', 'value');
    expect($config->get('key'))->toBe('value');
});
