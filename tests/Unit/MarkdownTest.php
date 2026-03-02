<?php

use CR\Markdown;

it('parses front matter from markdown content', function () {
    $md = new Markdown();
    $tmpFile = tempnam(sys_get_temp_dir(), 'md_');
    file_put_contents($tmpFile, "---\ntitle: Test\nslug: test-post\n---\n\nHello world");

    $md->loadFile($tmpFile);
    unlink($tmpFile);

    expect($md->frontMatter())->toBeArray()
        ->and($md->frontMatter()['title'])->toBe('Test')
        ->and($md->frontMatter()['slug'])->toBe('test-post');
});

it('returns raw markdown without front matter', function () {
    $md = new Markdown();
    $tmpFile = tempnam(sys_get_temp_dir(), 'md_');
    file_put_contents($tmpFile, "---\ntitle: Test\n---\n\nHello world");

    $md->loadFile($tmpFile);
    unlink($tmpFile);

    expect($md->rawMarkdown())->toBe('Hello world');
});

it('converts markdown to HTML', function () {
    $md = new Markdown();
    $tmpFile = tempnam(sys_get_temp_dir(), 'md_');
    file_put_contents($tmpFile, "---\ntitle: Test\n---\n\n**bold text**");

    $md->loadFile($tmpFile);
    unlink($tmpFile);

    expect($md->html())->toContain('<strong>bold text</strong>');
});

it('returns stripped markdown without inline HTML tags', function () {
    $md = new Markdown();
    $tmpFile = tempnam(sys_get_temp_dir(), 'md_');
    file_put_contents($tmpFile, "---\ntitle: Test\n---\n\nHello <br> world <img src=\"test.jpg\"> end");

    $md->loadFile($tmpFile);
    unlink($tmpFile);

    expect($md->strippedMarkdown())->not->toContain('<br>')
        ->and($md->strippedMarkdown())->not->toContain('<img')
        ->and($md->strippedMarkdown())->toContain('Hello')
        ->and($md->strippedMarkdown())->toContain('world');
});

it('handles file without front matter', function () {
    $md = new Markdown();
    $tmpFile = tempnam(sys_get_temp_dir(), 'md_');
    file_put_contents($tmpFile, "Just some markdown content");

    $md->loadFile($tmpFile);
    unlink($tmpFile);

    expect($md->frontMatter())->toBeFalse()
        ->and($md->rawMarkdown())->toBe('Just some markdown content');
});

it('returns false for non-existent file', function () {
    $md = new Markdown();
    $result = @$md->loadFile('/nonexistent/file.md');

    expect($result)->toBeFalse();
});
