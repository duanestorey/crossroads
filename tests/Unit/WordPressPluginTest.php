<?php

use CR\Config;
use CR\Plugins\WordPressPlugin;

function buildWpEntry(string $html): stdClass
{
    $entry = new stdClass();
    $entry->html = $html;

    return $entry;
}

it('removes caption shortcodes and wraps in caption span', function () {
    $config = new Config(['site.name' => 'Test']);
    $plugin = new WordPressPlugin($config);

    $entry = buildWpEntry(
        '[caption id="attachment_1" align="aligncenter" width="600"]<a href="http://example.com"><img src="photo.jpg" width="600"></a> This is a caption[/caption]'
    );

    $result = $plugin->contentFilter($entry);

    expect($result->html)->not->toContain('[caption')
        ->and($result->html)->not->toContain('[/caption]')
        ->and($result->html)->toContain('<span class="caption text-center fst-italic">')
        ->and($result->html)->toContain('</span>');
});

it('fixes self-closing img tags', function () {
    $config = new Config(['site.name' => 'Test']);
    $plugin = new WordPressPlugin($config);

    $entry = buildWpEntry('<img src="photo.jpg" />');
    $result = $plugin->contentFilter($entry);

    expect($result->html)->toBe('<img src="photo.jpg">')
        ->and($result->html)->not->toContain('/>');
});

it('fixes img tags with no space before self-closing slash', function () {
    $config = new Config(['site.name' => 'Test']);
    $plugin = new WordPressPlugin($config);

    $entry = buildWpEntry('<img src="photo.jpg"/>');
    $result = $plugin->contentFilter($entry);

    expect($result->html)->toBe('<img src="photo.jpg">')
        ->and($result->html)->not->toContain('/>');
});

it('handles content with no captions or self-closing images', function () {
    $config = new Config(['site.name' => 'Test']);
    $plugin = new WordPressPlugin($config);

    $html = '<p>This is a normal paragraph with <strong>bold</strong> text.</p>';
    $entry = buildWpEntry($html);
    $result = $plugin->contentFilter($entry);

    expect($result->html)->toBe($html);
});

it('processOne modifies html via contentFilter', function () {
    $config = new Config(['site.name' => 'Test']);
    $plugin = new WordPressPlugin($config);

    $entry = buildWpEntry(
        '[caption id="test"]<img src="photo.jpg" /> A caption[/caption]'
    );

    $result = $plugin->processOne($entry);

    expect($result->html)->not->toContain('[caption')
        ->and($result->html)->not->toContain('/>')
        ->and($result->html)->toContain('caption text-center fst-italic');
});

it('templateParamFilter returns params unchanged', function () {
    $config = new Config(['site.name' => 'Test']);
    $plugin = new WordPressPlugin($config);

    $params = new stdClass();
    $params->page = new stdClass();
    $params->page->title = 'Test Page';
    $params->isSingle = true;

    $result = $plugin->templateParamFilter($params);

    expect($result)->toBe($params)
        ->and($result->page->title)->toBe('Test Page')
        ->and($result->isSingle)->toBeTrue();
});

it('handles multiple caption shortcodes in same content', function () {
    $config = new Config(['site.name' => 'Test']);
    $plugin = new WordPressPlugin($config);

    $entry = buildWpEntry(
        '<p>Text before</p>'
        . '[caption id="one"]<a href="#"><img src="a.jpg"></a> First caption[/caption]'
        . '<p>Middle text</p>'
        . '[caption id="two"]<a href="#"><img src="b.jpg"></a> Second caption[/caption]'
        . '<p>Text after</p>'
    );

    $result = $plugin->contentFilter($entry);

    expect($result->html)->not->toContain('[caption')
        ->and($result->html)->not->toContain('[/caption]')
        ->and($result->html)->toContain('First caption')
        ->and($result->html)->toContain('Second caption')
        ->and($result->html)->toContain('<p>Text before</p>')
        ->and($result->html)->toContain('<p>Text after</p>');
});

it('handles multiple self-closing img tags', function () {
    $config = new Config(['site.name' => 'Test']);
    $plugin = new WordPressPlugin($config);

    $entry = buildWpEntry('<img src="a.jpg" /><img src="b.jpg"/>');
    $result = $plugin->contentFilter($entry);

    expect($result->html)->not->toContain('/>')
        ->and($result->html)->toContain('<img src="a.jpg">')
        ->and($result->html)->toContain('<img src="b.jpg">');
});
