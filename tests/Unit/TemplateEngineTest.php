<?php

use CR\Config;
use CR\Log;
use CR\LogListener;
use CR\TemplateEngine;

afterEach(function () {
    Log::instance()->listeners = [];

    // Clean up temp directories created during tests
    if (isset($this->tempDirs)) {
        foreach ($this->tempDirs as $dir) {
            if (is_dir($dir)) {
                $files = glob($dir . '/*');
                if ($files) {
                    foreach ($files as $file) {
                        if (is_file($file)) {
                            unlink($file);
                        }
                    }
                }
                rmdir($dir);
            }
        }
    }
});

/**
 * Helper: create a temp directory and register it for cleanup.
 */
function createTempTemplateDir(object $test): string
{
    $dir = sys_get_temp_dir() . '/cr_test_tpl_' . uniqid();
    mkdir($dir, 0755, true);

    if (!isset($test->tempDirs)) {
        $test->tempDirs = [];
    }
    $test->tempDirs[] = $dir;

    return $dir;
}

describe('setTemplateDirs', function () {
    it('updates template directories', function () {
        $engine = new TemplateEngine(new Config(null));
        $dir = createTempTemplateDir($this);

        $engine->setTemplateDirs([$dir]);

        expect($engine->templateDirs)->toBe([$dir]);
    });

    it('accepts multiple directories', function () {
        $engine = new TemplateEngine(new Config(null));
        $dir1 = createTempTemplateDir($this);
        $dir2 = createTempTemplateDir($this);

        $engine->setTemplateDirs([$dir1, $dir2]);

        expect($engine->templateDirs)->toBe([$dir1, $dir2]);
    });
});

describe('templateExists', function () {
    it('returns true for an existing .latte file', function () {
        $engine = new TemplateEngine(new Config(null));
        $dir = createTempTemplateDir($this);
        file_put_contents($dir . '/index.latte', '{$content}');

        $engine->setTemplateDirs([$dir]);

        expect($engine->templateExists('index'))->toBeTrue();
    });

    it('returns false for a missing template', function () {
        $engine = new TemplateEngine(new Config(null));
        $dir = createTempTemplateDir($this);

        $engine->setTemplateDirs([$dir]);

        expect($engine->templateExists('nonexistent'))->toBeFalse();
    });

    it('searches across multiple directories', function () {
        $engine = new TemplateEngine(new Config(null));
        $dir1 = createTempTemplateDir($this);
        $dir2 = createTempTemplateDir($this);
        file_put_contents($dir2 . '/footer.latte', '{$footer}');

        $engine->setTemplateDirs([$dir1, $dir2]);

        expect($engine->templateExists('footer'))->toBeTrue();
    });

    it('returns false when no directories are set', function () {
        $engine = new TemplateEngine(new Config(null));
        $engine->setTemplateDirs([]);

        expect($engine->templateExists('anything'))->toBeFalse();
    });
});

describe('locateTemplate', function () {
    it('returns template name when found as a string argument', function () {
        $engine = new TemplateEngine(new Config(null));
        $dir = createTempTemplateDir($this);
        file_put_contents($dir . '/single.latte', '{$content}');

        $engine->setTemplateDirs([$dir]);

        expect($engine->locateTemplate('single'))->toBe('single');
    });

    it('returns first matching template from an array', function () {
        $engine = new TemplateEngine(new Config(null));
        $dir = createTempTemplateDir($this);
        file_put_contents($dir . '/post-single.latte', '{$content}');

        $engine->setTemplateDirs([$dir]);

        $result = $engine->locateTemplate(['missing-template', 'post-single', 'fallback']);

        expect($result)->toBe('post-single');
    });

    it('returns false when no templates match', function () {
        $engine = new TemplateEngine(new Config(null));
        $dir = createTempTemplateDir($this);

        $engine->setTemplateDirs([$dir]);

        expect($engine->locateTemplate('nonexistent'))->toBeFalse();
    });

    it('returns false for an empty array', function () {
        $engine = new TemplateEngine(new Config(null));
        $dir = createTempTemplateDir($this);

        $engine->setTemplateDirs([$dir]);

        expect($engine->locateTemplate([]))->toBeFalse();
    });

    it('returns the first match even if multiple templates exist', function () {
        $engine = new TemplateEngine(new Config(null));
        $dir = createTempTemplateDir($this);
        file_put_contents($dir . '/alpha.latte', '{$a}');
        file_put_contents($dir . '/beta.latte', '{$b}');

        $engine->setTemplateDirs([$dir]);

        $result = $engine->locateTemplate(['alpha', 'beta']);

        expect($result)->toBe('alpha');
    });

    it('skips missing templates and returns the first found', function () {
        $engine = new TemplateEngine(new Config(null));
        $dir = createTempTemplateDir($this);
        file_put_contents($dir . '/existing.latte', '{$content}');

        $engine->setTemplateDirs([$dir]);

        $result = $engine->locateTemplate(['missing1', 'missing2', 'existing']);

        expect($result)->toBe('existing');
    });
});

describe('constructor', function () {
    it('initializes a Latte engine instance', function () {
        $engine = new TemplateEngine(new Config(null));

        expect($engine->latte)->toBeInstanceOf(\Latte\Engine::class);
    });

    it('stores the config reference', function () {
        $config = new Config(['site.lang' => 'es']);
        $engine = new TemplateEngine($config);

        expect($engine->config)->toBe($config);
    });
});
