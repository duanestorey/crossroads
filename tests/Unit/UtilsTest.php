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
