<?php

use CR\Config;
use CR\Plugins\SeoPlugin;

function buildSingleParams(object $content): stdClass
{
    $params = new stdClass();
    $params->isSingle = true;
    $params->isHome = false;
    $params->content = $content;
    $params->page = new stdClass();
    $params->page->title = $content->title;
    $params->page->description = $content->description;
    $params->page->seoMeta = '';

    return $params;
}

function buildContent(array $overrides = []): stdClass
{
    $content = new stdClass();
    $content->title = $overrides['title'] ?? 'Test Post Title';
    $content->description = $overrides['description'] ?? 'A short description of the test post.';
    $content->url = $overrides['url'] ?? 'https://example.com/posts/test-post.html';
    $content->publishDate = $overrides['publishDate'] ?? strtotime('2025-01-15');
    $content->modifiedDate = $overrides['modifiedDate'] ?? strtotime('2025-02-10');
    $content->featuredImageData = $overrides['featuredImageData'] ?? null;
    $content->featuredImage = $overrides['featuredImage'] ?? null;

    return $content;
}

function buildConfig(array $overrides = []): Config
{
    return new Config(array_merge([
        'site.name' => 'My Test Blog',
        'site.url' => 'https://example.com',
        'site.lang' => 'en',
        'site.social' => ['x' => 'https://x.com/testuser'],
    ], $overrides));
}

it('processOne returns content unchanged', function () {
    $config = buildConfig();
    $plugin = new SeoPlugin($config);

    $content = buildContent();
    $result = $plugin->processOne($content);

    expect($result)->toBe($content)
        ->and($result->title)->toBe('Test Post Title')
        ->and($result->url)->toBe('https://example.com/posts/test-post.html');
});

it('generates canonical link for single pages', function () {
    $config = buildConfig();
    $plugin = new SeoPlugin($config);

    $content = buildContent();
    $params = buildSingleParams($content);
    $result = $plugin->templateParamFilter($params);

    expect($result->page->seoMeta)->toContain('<link rel="canonical" href="https://example.com/posts/test-post.html">');
});

it('generates OG tags for single pages', function () {
    $config = buildConfig();
    $plugin = new SeoPlugin($config);

    $content = buildContent();
    $params = buildSingleParams($content);
    $result = $plugin->templateParamFilter($params);

    $seo = $result->page->seoMeta;

    expect($seo)->toContain('<meta property="og:title" content="Test Post Title">')
        ->and($seo)->toContain('<meta property="og:description" content="A short description of the test post.">')
        ->and($seo)->toContain('<meta property="og:url" content="https://example.com/posts/test-post.html">')
        ->and($seo)->toContain('<meta property="og:type" content="article">')
        ->and($seo)->toContain('<meta property="og:site_name" content="My Test Blog">');
});

it('generates Twitter card meta for single pages', function () {
    $config = buildConfig();
    $plugin = new SeoPlugin($config);

    $content = buildContent();
    $params = buildSingleParams($content);
    $result = $plugin->templateParamFilter($params);

    $seo = $result->page->seoMeta;

    expect($seo)->toContain('<meta name="twitter:card" content="summary">')
        ->and($seo)->toContain('<meta name="twitter:title" content="Test Post Title">')
        ->and($seo)->toContain('<meta name="twitter:description" content="A short description of the test post.">');
});

it('generates Twitter summary_large_image card when featured image exists', function () {
    $config = buildConfig();
    $plugin = new SeoPlugin($config);

    $imageData = new stdClass();
    $imageData->public_url = 'https://example.com/images/featured.jpg';
    $imageData->width = 1200;
    $imageData->height = 630;

    $content = buildContent([
        'featuredImageData' => $imageData,
        'featuredImage' => 'featured.jpg',
    ]);
    $params = buildSingleParams($content);
    $result = $plugin->templateParamFilter($params);

    $seo = $result->page->seoMeta;

    expect($seo)->toContain('<meta name="twitter:card" content="summary_large_image">')
        ->and($seo)->toContain('<meta name="twitter:image" content="https://example.com/images/featured.jpg">')
        ->and($seo)->toContain('<meta property="og:image" content="https://example.com/images/featured.jpg">')
        ->and($seo)->toContain('<meta property="og:image:width" content="1200">')
        ->and($seo)->toContain('<meta property="og:image:height" content="630">');
});

it('generates JSON-LD BlogPosting for single pages', function () {
    $config = buildConfig();
    $plugin = new SeoPlugin($config);

    $content = buildContent();
    $params = buildSingleParams($content);
    $result = $plugin->templateParamFilter($params);

    $seo = $result->page->seoMeta;

    expect($seo)->toContain('<script type="application/ld+json">')
        ->and($seo)->toContain('"@type":"BlogPosting"')
        ->and($seo)->toContain('"headline":"Test Post Title"')
        ->and($seo)->toContain('"datePublished":"2025-01-15"')
        ->and($seo)->toContain('"dateModified":"2025-02-10"')
        ->and($seo)->toContain('"name":"My Test Blog"');
});

it('generates WebSite JSON-LD for home page', function () {
    $config = buildConfig();
    $plugin = new SeoPlugin($config);

    $params = new stdClass();
    $params->isSingle = false;
    $params->isHome = true;
    $params->page = new stdClass();
    $params->page->title = 'My Test Blog';
    $params->page->description = 'A personal blog';
    $params->page->seoMeta = '';

    $result = $plugin->templateParamFilter($params);

    $seo = $result->page->seoMeta;

    expect($seo)->toContain('<script type="application/ld+json">')
        ->and($seo)->toContain('"@type":"WebSite"')
        ->and($seo)->toContain('"name":"My Test Blog"')
        ->and($seo)->toContain('"url":"https://example.com"');
});

it('extracts Twitter handle from x.com URL', function () {
    $config = buildConfig([
        'site.social' => ['x' => 'https://x.com/johndoe'],
    ]);
    $plugin = new SeoPlugin($config);

    $content = buildContent();
    $params = buildSingleParams($content);
    $result = $plugin->templateParamFilter($params);

    $seo = $result->page->seoMeta;

    expect($seo)->toContain('<meta name="twitter:site" content="@johndoe">')
        ->and($seo)->toContain('<meta name="twitter:creator" content="@johndoe">');
});

it('omits Twitter site/creator when no social config', function () {
    $config = buildConfig([
        'site.social' => [],
    ]);
    $plugin = new SeoPlugin($config);

    $content = buildContent();
    $params = buildSingleParams($content);
    $result = $plugin->templateParamFilter($params);

    $seo = $result->page->seoMeta;

    expect($seo)->not->toContain('twitter:site')
        ->and($seo)->not->toContain('twitter:creator');
});

it('resolves en to en_US in OG locale', function () {
    $config = buildConfig(['site.lang' => 'en']);
    $plugin = new SeoPlugin($config);

    $content = buildContent();
    $params = buildSingleParams($content);
    $result = $plugin->templateParamFilter($params);

    expect($result->page->seoMeta)->toContain('<meta property="og:locale" content="en_US">');
});

it('resolves fr to fr_FR in OG locale', function () {
    $config = buildConfig(['site.lang' => 'fr']);
    $plugin = new SeoPlugin($config);

    $content = buildContent();
    $params = buildSingleParams($content);
    $result = $plugin->templateParamFilter($params);

    expect($result->page->seoMeta)->toContain('<meta property="og:locale" content="fr_FR">');
});

it('adds noai robots directive when options.noai is true', function () {
    $config = buildConfig(['options.noai' => true]);
    $plugin = new SeoPlugin($config);

    $content = buildContent();
    $params = buildSingleParams($content);
    $result = $plugin->templateParamFilter($params);

    $seo = $result->page->seoMeta;

    expect($seo)->toContain('<meta name="robots" content="noai, noimageai">');
});

it('omits noai robots directive when options.noai is false', function () {
    $config = buildConfig(['options.noai' => false]);
    $plugin = new SeoPlugin($config);

    $content = buildContent();
    $params = buildSingleParams($content);
    $result = $plugin->templateParamFilter($params);

    expect($result->page->seoMeta)->not->toContain('noai');
});
