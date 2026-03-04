<?php

use CR\Config;
use CR\Log;
use CR\Menu;
use CR\PluginManager;
use CR\Renderer;
use CR\TemplateEngine;
use CR\Theme;

/**
 * Helper: recursively remove a directory and all its contents.
 */
function removeRendererTempDir(string $dir): void
{
    if (!is_dir($dir)) {
        return;
    }

    $items = scandir($dir);
    foreach ($items as $item) {
        if ($item === '.' || $item === '..') {
            continue;
        }
        $path = $dir . '/' . $item;
        if (is_dir($path)) {
            removeRendererTempDir($path);
        } else {
            unlink($path);
        }
    }
    rmdir($dir);
}

afterEach(function () {
    Log::instance()->listeners = [];

    // Clean up temp directories created during tests
    if (isset($this->tempDirs)) {
        foreach (array_reverse($this->tempDirs) as $dir) {
            removeRendererTempDir($dir);
        }
    }
});

/**
 * Helper: call a private/protected method on an object via reflection.
 */
function callRendererMethod(object $obj, string $method, array $args = []): mixed
{
    $ref = new ReflectionMethod($obj, $method);
    return $ref->invokeArgs($obj, $args);
}

/**
 * Helper: create a minimal Renderer for testing.
 *
 * Requires a loaded Theme (with themeConfig set) so getAssetHash() works.
 */
function createTestRenderer(object $test): Renderer
{
    $config = new Config(null);
    $templateEngine = new TemplateEngine($config);
    $pluginManager = new PluginManager($config);
    $menu = new Menu();

    // Create a temp theme directory with theme.yaml and index.latte
    $base = sys_get_temp_dir() . '/cr_test_renderer_' . uniqid();
    $coreDir = $base . '/core';
    $localDir = $base . '/local';
    $themeDir = $coreDir . '/testtheme';

    mkdir($themeDir, 0755, true);
    mkdir($localDir, 0755, true);

    if (!isset($test->tempDirs)) {
        $test->tempDirs = [];
    }
    $test->tempDirs[] = $themeDir;
    $test->tempDirs[] = $coreDir;
    $test->tempDirs[] = $localDir;
    $test->tempDirs[] = $base;

    file_put_contents($themeDir . '/theme.yaml', "theme:\n  name: testtheme\n  assets: {}");
    file_put_contents($themeDir . '/index.latte', '{$content}');

    $theme = new Theme('testtheme', $coreDir, $localDir);
    $theme->load();

    return new Renderer($config, $templateEngine, $pluginManager, $menu, $theme);
}

describe('constants', function () {
    it('defines HOME as 0', function () {
        expect(Renderer::HOME)->toBe(0);
    });

    it('defines TAXONOMY as 1', function () {
        expect(Renderer::TAXONOMY)->toBe(1);
    });

    it('defines CONTENT as 2', function () {
        expect(Renderer::CONTENT)->toBe(2);
    });

    it('defines AUTHOR as 3', function () {
        expect(Renderer::AUTHOR)->toBe(3);
    });
});

describe('_getPaginationLinks', function () {
    it('returns one link for a single page', function () {
        $renderer = createTestRenderer($this);
        $links = callRendererMethod($renderer, '_getPaginationLinks', ['/blog', 1]);

        expect($links)->toHaveCount(1)
            ->and($links[0]->num)->toBe(1)
            ->and($links[0]->url)->toBe('/blog/index.html');
    });

    it('returns correct links for three pages', function () {
        $renderer = createTestRenderer($this);
        $links = callRendererMethod($renderer, '_getPaginationLinks', ['/blog', 3]);

        expect($links)->toHaveCount(3)
            ->and($links[0]->url)->toBe('/blog/index.html')
            ->and($links[0]->num)->toBe(1)
            ->and($links[1]->url)->toBe('/blog/index-page-2.html')
            ->and($links[1]->num)->toBe(2)
            ->and($links[2]->url)->toBe('/blog/index-page-3.html')
            ->and($links[2]->num)->toBe(3);
    });

    it('returns an empty array for zero pages', function () {
        $renderer = createTestRenderer($this);
        $links = callRendererMethod($renderer, '_getPaginationLinks', ['/blog', 0]);

        expect($links)->toBeArray()
            ->and($links)->toBeEmpty();
    });

    it('handles an empty path prefix', function () {
        $renderer = createTestRenderer($this);
        $links = callRendererMethod($renderer, '_getPaginationLinks', ['', 2]);

        expect($links)->toHaveCount(2)
            ->and($links[0]->url)->toBe('/index.html')
            ->and($links[1]->url)->toBe('/index-page-2.html');
    });
});
