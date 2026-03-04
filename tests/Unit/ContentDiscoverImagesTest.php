<?php

use CR\Content;
use CR\Config;

beforeEach(function () {
    $this->config = new Config(null);
});

describe('discoverImages', function () {
    it('finds a single image with double-quoted src', function () {
        $content = new Content($this->config, 'posts', []);
        $content->originalHtml = '<img src="photo.jpg">';

        $images = $content->discoverImages();

        expect($images)->toHaveCount(1)
            ->and(array_values($images))->toContain('photo.jpg');
    });

    it('finds multiple images', function () {
        $content = new Content($this->config, 'posts', []);
        $content->originalHtml = '<img src="one.jpg"><p>text</p><img src="two.png">';

        $images = $content->discoverImages();

        expect($images)->toHaveCount(2)
            ->and(array_values($images))->toContain('one.jpg')
            ->and(array_values($images))->toContain('two.png');
    });

    it('finds image with single-quoted src', function () {
        $content = new Content($this->config, 'posts', []);
        $content->originalHtml = "<img src='photo.jpg'>";

        $images = $content->discoverImages();

        expect($images)->toHaveCount(1)
            ->and(array_values($images))->toContain('photo.jpg');
    });

    it('returns empty array when no images present', function () {
        $content = new Content($this->config, 'posts', []);
        $content->originalHtml = '<p>No images here</p>';

        $images = $content->discoverImages();

        expect($images)->toBe([]);
    });

    it('finds image with path in src', function () {
        $content = new Content($this->config, 'posts', []);
        $content->originalHtml = '<img src="images/photo.jpg">';

        $images = $content->discoverImages();

        expect($images)->toHaveCount(1)
            ->and(array_values($images))->toContain('images/photo.jpg');
    });

    it('finds image src among other attributes', function () {
        $content = new Content($this->config, 'posts', []);
        $content->originalHtml = '<img class="foo" src="bar.png" alt="test">';

        $images = $content->discoverImages();

        expect($images)->toHaveCount(1)
            ->and(array_values($images))->toContain('bar.png');
    });
});
