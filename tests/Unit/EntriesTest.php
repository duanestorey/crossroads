<?php

use CR\Config;
use CR\Content;
use CR\Entries;
use CR\PluginManager;

beforeEach(function () {
    $this->config = new Config([
        'site.url' => 'https://example.com',
        'content' => [
            'posts' => ['base' => '/posts'],
            'pages' => ['base' => '/pages'],
        ],
    ]);
    $this->pluginManager = new PluginManager($this->config);
    $this->entries = new Entries($this->config, null, $this->pluginManager);
});

it('returns empty array from get() for unknown content type', function () {
    expect($this->entries->get('nonexistent'))->toBe([]);
});

it('returns empty array from getTaxTypes() for unknown content type', function () {
    expect($this->entries->getTaxTypes('nonexistent'))->toBe([]);
});

it('returns empty array from getTaxTerms() for unknown content type', function () {
    expect($this->entries->getTaxTerms('nonexistent', 'category'))->toBe([]);
});

it('returns empty array from getTax() for unknown content type', function () {
    expect($this->entries->getTax('nonexistent', 'category', 'php'))->toBe([]);
});

it('returns entry count of zero initially', function () {
    expect($this->entries->getEntryCount())->toBe(0);
});

it('returns entries after direct population', function () {
    $content = new Content($this->config, 'posts', ['base' => '/posts']);
    $content->slug = 'test-post';
    $content->title = 'Test Post';
    $content->html = 'Hello world';
    $content->calculate();

    $this->entries->entries['posts'] = [$content];
    $this->entries->totalEntries = 1;

    expect($this->entries->get('posts'))->toHaveCount(1)
        ->and($this->entries->get('posts')[0]->title)->toBe('Test Post')
        ->and($this->entries->getEntryCount())->toBe(1);
});

it('returns all entries across content types', function () {
    $post = new Content($this->config, 'posts', ['base' => '/posts']);
    $post->slug = 'my-post';
    $post->html = 'post content';
    $post->calculate();

    $page = new Content($this->config, 'pages', ['base' => '/pages']);
    $page->slug = 'about';
    $page->html = 'page content';
    $page->calculate();

    $this->entries->entries['posts'] = [$post];
    $this->entries->entries['pages'] = [$page];

    $all = $this->entries->getAll();
    expect($all)->toHaveCount(2);
});

it('returns taxonomy types for a content type', function () {
    $this->entries->tax['posts'] = [
        'categories' => ['php' => [], 'javascript' => []],
        'tags' => ['tutorial' => []],
    ];

    $types = $this->entries->getTaxTypes('posts');
    expect($types)->toBe(['categories', 'tags']);
});

it('returns taxonomy terms for a type', function () {
    $content = new Content($this->config, 'posts', ['base' => '/posts']);
    $content->slug = 'test';
    $content->html = 'test';

    $this->entries->tax['posts'] = [
        'categories' => [
            'php' => [$content],
            'javascript' => [$content],
        ],
    ];

    $terms = $this->entries->getTaxTerms('posts', 'categories');
    expect($terms)->toBe(['javascript', 'php']);
});

it('returns entries for a specific taxonomy term', function () {
    $content = new Content($this->config, 'posts', ['base' => '/posts']);
    $content->slug = 'test';
    $content->title = 'PHP Post';
    $content->html = 'test';

    $this->entries->tax['posts'] = [
        'categories' => [
            'php' => [$content],
        ],
    ];

    $results = $this->entries->getTax('posts', 'categories', 'php');
    expect($results)->toHaveCount(1)
        ->and($results[0]->title)->toBe('PHP Post');
});
