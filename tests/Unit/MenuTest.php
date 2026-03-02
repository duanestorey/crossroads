<?php

use CR\Menu;

beforeEach(function () {
    $this->menu = new Menu();
    $this->menu->menuData = [
        'main' => [
            'Home' => '/',
            'Blog' => '/blog',
            'About' => '/about',
        ],
        'footer' => [
            'Privacy' => '/privacy',
            'Terms' => '/terms',
        ],
    ];
});

describe('isAvailable', function () {
    it('returns false for unknown menu', function () {
        expect($this->menu->isAvailable('sidebar'))->toBeFalse();
    });

    it('returns true for known menu', function () {
        expect($this->menu->isAvailable('main'))->toBeTrue();
        expect($this->menu->isAvailable('footer'))->toBeTrue();
    });
});

describe('getAvailable', function () {
    it('returns list of menu names', function () {
        $available = $this->menu->getAvailable();

        expect($available)->toBeArray()
            ->and($available)->toContain('main')
            ->and($available)->toContain('footer')
            ->and($available)->toHaveCount(2);
    });

    it('returns empty array when no menus defined', function () {
        $this->menu->menuData = [];
        expect($this->menu->getAvailable())->toBe([]);
    });
});

describe('build', function () {
    it('returns false for unknown menu', function () {
        expect($this->menu->build('nonexistent', '/'))->toBeFalse();
    });

    it('returns array of stdClass items with name, url, and isActive', function () {
        $items = $this->menu->build('main', '/nowhere');

        expect($items)->toBeArray()
            ->and($items)->toHaveCount(3);

        foreach ($items as $item) {
            expect($item)->toBeInstanceOf(\stdClass::class)
                ->and($item)->toHaveProperty('name')
                ->and($item)->toHaveProperty('url')
                ->and($item)->toHaveProperty('isActive');
        }

        expect($items[0]->name)->toBe('Home')
            ->and($items[0]->url)->toBe('/')
            ->and($items[1]->name)->toBe('Blog')
            ->and($items[1]->url)->toBe('/blog')
            ->and($items[2]->name)->toBe('About')
            ->and($items[2]->url)->toBe('/about');
    });

    it('marks matching URL as active', function () {
        $items = $this->menu->build('main', '/blog');

        $activeItems = array_filter($items, fn ($item) => $item->isActive);
        expect($activeItems)->toHaveCount(1);

        $activeItem = array_values($activeItems)[0];
        expect($activeItem->name)->toBe('Blog')
            ->and($activeItem->url)->toBe('/blog');
    });

    it('does not mark non-matching URLs as active', function () {
        $items = $this->menu->build('main', '/contact');

        foreach ($items as $item) {
            expect($item->isActive)->toBeFalse();
        }
    });

    it('handles trailing slash normalization for active detection', function () {
        $items = $this->menu->build('main', '/blog/');

        $activeItems = array_filter($items, fn ($item) => $item->isActive);
        expect($activeItems)->toHaveCount(1);

        $activeItem = array_values($activeItems)[0];
        expect($activeItem->name)->toBe('Blog');
    });
});
