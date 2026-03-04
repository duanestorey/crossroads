<?php

use CR\Log;
use CR\LogListener;
use CR\Theme;

/**
 * Helper: recursively remove a directory and all its contents.
 */
function removeDirectoryRecursive(string $dir): void
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
            removeDirectoryRecursive($path);
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
        // Remove in reverse order so children are deleted before parents
        foreach (array_reverse($this->tempDirs) as $dir) {
            removeDirectoryRecursive($dir);
        }
    }
});

/**
 * Helper: create a temp directory tree for theme testing and register for cleanup.
 *
 * Returns an array with 'core' and 'local' directory paths.
 */
function createThemeDirs(object $test): array
{
    $base = sys_get_temp_dir() . '/cr_test_theme_' . uniqid();
    $core = $base . '/core';
    $local = $base . '/local';

    mkdir($core, 0755, true);
    mkdir($local, 0755, true);

    if (!isset($test->tempDirs)) {
        $test->tempDirs = [];
    }
    // Register all dirs for cleanup (children first via reverse in afterEach)
    $test->tempDirs[] = $core;
    $test->tempDirs[] = $local;
    $test->tempDirs[] = $base;

    return ['core' => $core, 'local' => $local];
}

/**
 * Helper: create a valid core theme directory with theme.yaml and index.latte.
 */
function createCoreTheme(object $test, string $coreDir, string $themeName, ?string $yamlContent = null): void
{
    $themeDir = $coreDir . '/' . $themeName;
    mkdir($themeDir, 0755, true);

    if (!isset($test->tempDirs)) {
        $test->tempDirs = [];
    }
    $test->tempDirs[] = $themeDir;

    if ($yamlContent === null) {
        $yamlContent = "theme:\n  name: " . $themeName . "\n  assets: {}";
    }

    file_put_contents($themeDir . '/theme.yaml', $yamlContent);
    file_put_contents($themeDir . '/index.latte', '{$content}');
}

describe('constructor and default accessors', function () {
    it('returns the theme name passed to the constructor', function () {
        $dirs = createThemeDirs($this);
        $theme = new Theme('mytheme', $dirs['core'], $dirs['local']);

        expect($theme->name())->toBe('mytheme');
    });

    it('returns false for isChildTheme before load', function () {
        $dirs = createThemeDirs($this);
        $theme = new Theme('mytheme', $dirs['core'], $dirs['local']);

        expect($theme->isChildTheme())->toBeFalse();
    });

    it('returns false for isLocalTheme before load', function () {
        $dirs = createThemeDirs($this);
        $theme = new Theme('mytheme', $dirs['core'], $dirs['local']);

        expect($theme->isLocalTheme())->toBeFalse();
    });

    it('returns null for getParentThemeName before load', function () {
        $dirs = createThemeDirs($this);
        $theme = new Theme('mytheme', $dirs['core'], $dirs['local']);

        expect($theme->getParentThemeName())->toBeNull();
    });

    it('returns null for primaryThemeDir before load', function () {
        $dirs = createThemeDirs($this);
        $theme = new Theme('mytheme', $dirs['core'], $dirs['local']);

        expect($theme->primaryThemeDir())->toBeNull();
    });

    it('getChildThemeName returns the same as name', function () {
        $dirs = createThemeDirs($this);
        $theme = new Theme('mytheme', $dirs['core'], $dirs['local']);

        expect($theme->getChildThemeName())->toBe('mytheme');
    });
});

describe('load', function () {
    it('returns true when a valid core theme exists', function () {
        $dirs = createThemeDirs($this);
        createCoreTheme($this, $dirs['core'], 'mytheme');

        $theme = new Theme('mytheme', $dirs['core'], $dirs['local']);

        expect($theme->load())->toBeTrue();
    });

    it('returns false when the theme does not exist', function () {
        $dirs = createThemeDirs($this);

        $theme = new Theme('nonexistent', $dirs['core'], $dirs['local']);

        expect($theme->load())->toBeFalse();
    });

    it('sets primaryThemeDir after successful load', function () {
        $dirs = createThemeDirs($this);
        createCoreTheme($this, $dirs['core'], 'mytheme');

        $theme = new Theme('mytheme', $dirs['core'], $dirs['local']);
        $theme->load();

        expect($theme->primaryThemeDir())->toBe($dirs['core'] . '/mytheme');
    });

    it('returns false when theme.yaml exists but index.latte is missing', function () {
        $dirs = createThemeDirs($this);
        $themeDir = $dirs['core'] . '/broken';
        mkdir($themeDir, 0755, true);

        if (!isset($this->tempDirs)) {
            $this->tempDirs = [];
        }
        $this->tempDirs[] = $themeDir;

        file_put_contents($themeDir . '/theme.yaml', "theme:\n  name: broken\n  assets: {}");
        // No index.latte

        $theme = new Theme('broken', $dirs['core'], $dirs['local']);

        expect($theme->load())->toBeFalse();
    });

    it('returns false when index.latte exists but theme.yaml is missing', function () {
        $dirs = createThemeDirs($this);
        $themeDir = $dirs['core'] . '/incomplete';
        mkdir($themeDir, 0755, true);

        if (!isset($this->tempDirs)) {
            $this->tempDirs = [];
        }
        $this->tempDirs[] = $themeDir;

        file_put_contents($themeDir . '/index.latte', '{$content}');
        // No theme.yaml

        $theme = new Theme('incomplete', $dirs['core'], $dirs['local']);

        expect($theme->load())->toBeFalse();
    });

    it('isLocalTheme remains false after loading a core theme', function () {
        $dirs = createThemeDirs($this);
        createCoreTheme($this, $dirs['core'], 'mytheme');

        $theme = new Theme('mytheme', $dirs['core'], $dirs['local']);
        $theme->load();

        expect($theme->isLocalTheme())->toBeFalse();
    });

    it('isChildTheme remains false after loading a core theme', function () {
        $dirs = createThemeDirs($this);
        createCoreTheme($this, $dirs['core'], 'mytheme');

        $theme = new Theme('mytheme', $dirs['core'], $dirs['local']);
        $theme->load();

        expect($theme->isChildTheme())->toBeFalse();
    });

    it('loads a local theme when it has theme.yaml and index.latte', function () {
        $dirs = createThemeDirs($this);
        $localThemeDir = $dirs['local'] . '/localtheme';
        mkdir($localThemeDir, 0755, true);

        if (!isset($this->tempDirs)) {
            $this->tempDirs = [];
        }
        $this->tempDirs[] = $localThemeDir;

        file_put_contents($localThemeDir . '/theme.yaml', "theme:\n  name: Local Theme\n  assets: {}");
        file_put_contents($localThemeDir . '/index.latte', '{$content}');

        $theme = new Theme('localtheme', $dirs['core'], $dirs['local']);
        $theme->load();

        expect($theme->isLocalTheme())->toBeTrue()
            ->and($theme->primaryThemeDir())->toBe($localThemeDir);
    });

    it('loads a child theme that references a core parent', function () {
        $dirs = createThemeDirs($this);

        // Create core parent theme
        createCoreTheme($this, $dirs['core'], 'parenttheme');

        // Create local child theme
        $childDir = $dirs['local'] . '/childtheme';
        mkdir($childDir, 0755, true);

        if (!isset($this->tempDirs)) {
            $this->tempDirs = [];
        }
        $this->tempDirs[] = $childDir;

        file_put_contents($childDir . '/theme.yaml', "theme:\n  name: Child Theme\n  parent: parenttheme\n  assets: {}");

        $theme = new Theme('childtheme', $dirs['core'], $dirs['local']);
        $theme->load();

        expect($theme->isChildTheme())->toBeTrue()
            ->and($theme->isLocalTheme())->toBeTrue()
            ->and($theme->getParentThemeName())->toBe('parenttheme')
            ->and($theme->primaryThemeDir())->toBe($dirs['core'] . '/parenttheme');
    });
});

describe('name accessor', function () {
    it('returns different names for different themes', function () {
        $dirs = createThemeDirs($this);

        $theme1 = new Theme('alpha', $dirs['core'], $dirs['local']);
        $theme2 = new Theme('beta', $dirs['core'], $dirs['local']);

        expect($theme1->name())->toBe('alpha')
            ->and($theme2->name())->toBe('beta');
    });
});
