<?php

use CR\Config;
use CR\Entries;
use CR\PluginManager;

beforeEach(function () {
    $this->config = new Config([
        'site.url' => 'https://example.com',
    ]);
    $this->pluginManager = new PluginManager($this->config);
    $this->entries = new Entries($this->config, null, $this->pluginManager);
});

it('returns empty array from get() for unknown content type', function () {
    $result = $this->entries->get('nonexistent');

    expect($result)->toBe([])
        ->and($result)->toBeArray();
});

it('returns empty array from getTaxTypes() for unknown content type', function () {
    $result = $this->entries->getTaxTypes('nonexistent');

    expect($result)->toBe([])
        ->and($result)->toBeArray();
});

it('returns empty array from getTaxTerms() for unknown content type', function () {
    $result = $this->entries->getTaxTerms('nonexistent', 'category');

    expect($result)->toBe([])
        ->and($result)->toBeArray();
});

it('returns empty array from getTax() for unknown content type', function () {
    $result = $this->entries->getTax('nonexistent', 'category', 'php');

    expect($result)->toBe([])
        ->and($result)->toBeArray();
});
