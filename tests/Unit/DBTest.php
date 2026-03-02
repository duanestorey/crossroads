<?php

use CR\Config;
use CR\Content;
use CR\DB;

function createTestContent(Config $config, string $slug, string $type = 'posts', array $overrides = []): Content
{
    $content = new Content($config, $type, ['base' => '/' . $type]);
    $content->slug = $slug;
    $content->title = $overrides['title'] ?? 'Title ' . $slug;
    $content->description = $overrides['description'] ?? '';
    $content->html = $overrides['html'] ?? '<p>Content</p>';
    $content->contentType = $type;
    $content->unique = md5($slug);
    $content->relUrl = '/' . $type . '/' . $slug . '.html';
    $content->featuredImage = $overrides['featuredImage'] ?? '';
    $content->publishDate = $overrides['publishDate'] ?? time();
    $content->modifiedDate = $overrides['modifiedDate'] ?? time();
    $content->contentPath = $type . '/' . $slug . '.md';
    $content->markdownData = $overrides['markdownData'] ?? '';
    $content->originalHtml = $overrides['html'] ?? '<p>Content</p>';
    $content->isDraft = $overrides['isDraft'] ?? false;
    $content->taxonomy = $overrides['taxonomy'] ?? [];
    $content->imageInfo = $overrides['imageInfo'] ?? [];

    return $content;
}

beforeEach(function () {
    if (!file_exists(CROSSROADS_DB_DIR)) {
        @mkdir(CROSSROADS_DB_DIR, 0755, true);
    }

    $dbFile = CROSSROADS_DB_DIR . '/db.sqlite';
    if (file_exists($dbFile)) {
        @unlink($dbFile);
    }

    $this->config = new Config([
        'site.url' => 'https://example.com',
    ]);

    $this->db = new DB($this->config);
    $this->db->rebuild();
});

afterEach(function () {
    unset($this->db);

    $dbFile = CROSSROADS_DB_DIR . '/db.sqlite';
    if (file_exists($dbFile)) {
        @unlink($dbFile);
    }
});

it('creates tables from schema files', function () {
    $result = $this->db->getAllContent();
    expect($result)->not->toBeFalse();
});

it('addContent inserts a content row', function () {
    $content = createTestContent($this->config, 'test-post', 'posts', [
        'title' => 'Test Post',
        'description' => 'A test post',
        'html' => '<p>Hello world</p>',
        'publishDate' => strtotime('2024-01-15'),
        'modifiedDate' => strtotime('2024-01-16'),
        'markdownData' => '# Test',
    ]);

    $this->db->addContent($content);

    $result = $this->db->getAllContent();
    $row = $result->fetchArray(SQLITE3_ASSOC);

    expect($row)->not->toBeFalse()
        ->and($row['title'])->toBe('Test Post')
        ->and($row['slug'])->toBe('test-post')
        ->and($row['type'])->toBe('posts');
});

it('getAllContent returns all inserted rows', function () {
    foreach (['post-1', 'post-2', 'post-3'] as $slug) {
        $this->db->addContent(createTestContent($this->config, $slug));
    }

    $result = $this->db->getAllContent();
    $count = 0;
    while ($result->fetchArray(SQLITE3_ASSOC)) {
        $count++;
    }

    expect($count)->toBe(3);
});

it('getContentType filters by type', function () {
    $this->db->addContent(createTestContent($this->config, 'my-post', 'posts', ['title' => 'A Post']));
    $this->db->addContent(createTestContent($this->config, 'about', 'pages', ['title' => 'About']));

    $result = $this->db->getContentType('posts');
    $count = 0;
    $titles = [];
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $count++;
        $titles[] = $row['title'];
    }

    expect($count)->toBe(1)
        ->and($titles[0])->toBe('A Post');
});

it('getAllTaxForContent returns taxonomy for a content ID', function () {
    $content = createTestContent($this->config, 'tax-post', 'posts', [
        'title' => 'Taxonomy Post',
        'taxonomy' => [
            'categories' => ['php', 'web'],
            'tags' => ['tutorial'],
        ],
    ]);

    $this->db->addContent($content);

    $allContent = $this->db->getAllContent();
    $row = $allContent->fetchArray(SQLITE3_ASSOC);
    $contentId = $row['id'];

    $taxResult = $this->db->getAllTaxForContent($contentId);
    $taxEntries = [];
    while ($taxRow = $taxResult->fetchArray(SQLITE3_ASSOC)) {
        $taxEntries[] = $taxRow;
    }

    expect($taxEntries)->toHaveCount(3);

    $terms = array_column($taxEntries, 'term');
    sort($terms);
    expect($terms)->toBe(['php', 'tutorial', 'web']);
});

it('getAllTerms queries taxonomy table not content table', function () {
    $content = createTestContent($this->config, 'term-test', 'posts', [
        'taxonomy' => ['categories' => ['php']],
    ]);

    $this->db->addContent($content);

    $result = $this->db->getAllTerms();
    $row = $result->fetchArray(SQLITE3_ASSOC);

    expect($row)->not->toBeFalse()
        ->and($row)->toHaveKey('tax')
        ->and($row)->toHaveKey('term')
        ->and($row['term'])->toBe('php');
});
