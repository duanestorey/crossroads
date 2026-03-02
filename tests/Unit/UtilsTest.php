<?php

use CR\Utils;

describe('fixPath', function () {
    it('removes trailing slash', function () {
        expect(Utils::fixPath('/some/path/'))->toBe('/some/path');
    });

    it('removes trailing backslash', function () {
        expect(Utils::fixPath('/some/path\\'))->toBe('/some/path');
    });

    it('leaves clean path unchanged', function () {
        expect(Utils::fixPath('/some/path'))->toBe('/some/path');
    });
});

describe('titleToSlug', function () {
    it('converts title to lowercase slug', function () {
        expect(Utils::titleToSlug('My Blog Post'))->toBe('my-blog-post');
    });

    it('removes special characters', function () {
        expect(Utils::titleToSlug('Hello, World! #2024'))->toBe('hello-world-2024');
    });

    it('converts underscores to hyphens', function () {
        expect(Utils::titleToSlug('my_cool_post'))->toBe('my-cool-post');
    });

    it('handles empty string', function () {
        expect(Utils::titleToSlug(''))->toBe('');
    });
});

describe('cleanTerm', function () {
    it('lowercases and replaces spaces with hyphens', function () {
        expect(Utils::cleanTerm('Web Development'))->toBe('web-development');
    });

    it('handles already clean terms', function () {
        expect(Utils::cleanTerm('php'))->toBe('php');
    });
});

describe('titleToSlug extended', function () {
    it('handles consecutive special characters', function () {
        $slug = Utils::titleToSlug('Hello---World');
        expect($slug)->toBe('hello---world');
    });

    it('strips unicode characters', function () {
        $slug = Utils::titleToSlug('Café Résumé');
        // titleToSlug strips non-ASCII characters
        expect($slug)->not->toContain('é')
            ->and($slug)->toContain('caf');
    });
});

describe('findAllFilesWithExtension', function () {
    it('finds files with matching extension in temp directory', function () {
        $tmpDir = sys_get_temp_dir() . '/cr_test_' . uniqid();
        mkdir($tmpDir);
        file_put_contents($tmpDir . '/test.php', '<?php');
        file_put_contents($tmpDir . '/test.txt', 'hello');
        file_put_contents($tmpDir . '/test.md', '# title');

        $result = Utils::findAllFilesWithExtension($tmpDir, 'php');

        expect($result)->toHaveCount(1)
            ->and($result[0])->toContain('test.php');

        // cleanup
        unlink($tmpDir . '/test.php');
        unlink($tmpDir . '/test.txt');
        unlink($tmpDir . '/test.md');
        rmdir($tmpDir);
    });

    it('returns empty array for non-existent directory', function () {
        $result = Utils::findAllFilesWithExtension('/nonexistent/dir/xyz', 'php');
        expect($result)->toBe([]);
    });

    it('finds files in nested subdirectories', function () {
        $tmpDir = sys_get_temp_dir() . '/cr_test_' . uniqid();
        mkdir($tmpDir);
        mkdir($tmpDir . '/sub');
        file_put_contents($tmpDir . '/top.md', '# top');
        file_put_contents($tmpDir . '/sub/nested.md', '# nested');

        $result = Utils::findAllFilesWithExtension($tmpDir, 'md');

        expect($result)->toHaveCount(2);

        // cleanup
        unlink($tmpDir . '/top.md');
        unlink($tmpDir . '/sub/nested.md');
        rmdir($tmpDir . '/sub');
        rmdir($tmpDir);
    });

    it('accepts array of extensions', function () {
        $tmpDir = sys_get_temp_dir() . '/cr_test_' . uniqid();
        mkdir($tmpDir);
        file_put_contents($tmpDir . '/test.php', '<?php');
        file_put_contents($tmpDir . '/test.md', '# title');
        file_put_contents($tmpDir . '/test.txt', 'hello');

        $result = Utils::findAllFilesWithExtension($tmpDir, ['php', 'md']);

        expect($result)->toHaveCount(2);

        // cleanup
        unlink($tmpDir . '/test.php');
        unlink($tmpDir . '/test.md');
        unlink($tmpDir . '/test.txt');
        rmdir($tmpDir);
    });
});
