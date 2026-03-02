<?php

use CR\Config;
use CR\Content;

beforeEach(function () {
    $this->config = new Config([
        'site.url' => 'https://example.com',
    ]);
    $this->contentConfig = ['base' => '/posts'];
});

it('constructs with correct defaults', function () {
    $content = new Content($this->config, 'posts', $this->contentConfig);

    expect($content->contentType)->toBe('posts')
        ->and($content->title)->toBe('')
        ->and($content->taxonomy)->toBe([]);
});

it('sets title', function () {
    $content = new Content($this->config, 'posts', $this->contentConfig);
    $content->setTitle('My Post');

    expect($content->title)->toBe('My Post');
});

it('generates excerpt from html', function () {
    $content = new Content($this->config, 'posts', $this->contentConfig);
    $content->html = str_repeat('word ', 200);

    $excerpt = $content->excerpt(50);

    expect(strlen($excerpt))->toBeLessThan(100)
        ->and($excerpt)->toEndWith('...');
});

it('generates excerpt without ellipsis', function () {
    $content = new Content($this->config, 'posts', $this->contentConfig);
    $content->html = 'Short content here';

    $excerpt = $content->excerpt(600, false);

    expect($excerpt)->not->toContain('...');
});

it('strips HTML tags from excerpt', function () {
    $content = new Content($this->config, 'posts', $this->contentConfig);
    $content->html = '<p><strong>Bold</strong> and <em>italic</em> text here</p>';

    $excerpt = $content->excerpt(600);

    expect($excerpt)->not->toContain('<p>')
        ->and($excerpt)->not->toContain('<strong>')
        ->and($excerpt)->toContain('Bold');
});

it('calculates word count and reading time', function () {
    $content = new Content($this->config, 'posts', $this->contentConfig);
    $content->slug = 'test-post';
    $content->html = str_repeat('word ', 500);

    $content->calculate();

    expect($content->words)->toBe(500)
        ->and($content->readingTime)->not->toBeEmpty()
        ->and($content->readingTime)->toContain('minute');
});

it('calculates URL from config base', function () {
    $content = new Content($this->config, 'posts', $this->contentConfig);
    $content->slug = 'my-post';
    $content->html = 'test';

    $content->calculate();

    expect($content->url)->toBe('https://example.com/posts/my-post.html')
        ->and($content->relUrl)->toBe('/posts/my-post.html');
});

it('calculates URL without config base', function () {
    $content = new Content($this->config, 'pages', []);
    $content->slug = 'about';
    $content->html = 'test';

    $content->calculate();

    expect($content->relUrl)->toBe('/pages/about.html');
});

it('excerpt measures character length not byte length for unicode', function () {
    $content = new Content($this->config, 'posts', $this->contentConfig);
    // Each accented word is 5 chars but more bytes in UTF-8
    $content->html = 'résumé café naïve résumé café naïve résumé café naïve résumé';

    $excerpt = $content->excerpt(30, false);

    // With mb_strlen, it counts characters not bytes, so more words fit within the limit
    $wordCount = str_word_count($excerpt);
    expect($wordCount)->toBeGreaterThan(3);
});
