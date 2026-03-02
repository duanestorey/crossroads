<?php

use CR\YAML;

describe('parse', function () {
    it('parses valid YAML string and returns array', function () {
        $result = YAML::parse("name: Test\nversion: 1");
        expect($result)->toBeArray()
            ->and($result['name'])->toBe('Test')
            ->and($result['version'])->toBe(1);
    });

    it('returns false for invalid YAML', function () {
        $result = YAML::parse(":\n  :\n    - :\n  invalid: [unclosed");
        expect($result)->toBeFalse();
    });
});

describe('parse_file', function () {
    it('parses valid YAML file and returns array', function () {
        $tmpFile = tempnam(sys_get_temp_dir(), 'yaml_');
        file_put_contents($tmpFile, "site:\n  name: My Blog\n  url: https://example.com\n");

        $result = YAML::parse_file($tmpFile);
        unlink($tmpFile);

        expect($result)->toBeArray()
            ->and($result['site']['name'])->toBe('My Blog')
            ->and($result['site']['url'])->toBe('https://example.com');
    });

    it('returns false for non-existent file', function () {
        $result = YAML::parse_file('/nonexistent/path/to/file.yaml');
        expect($result)->toBeFalse();
    });

    it('returns flattened result when $flatten is true', function () {
        $tmpFile = tempnam(sys_get_temp_dir(), 'yaml_');
        file_put_contents($tmpFile, "site:\n  name: My Blog\n  url: https://example.com\n");

        $result = YAML::parse_file($tmpFile, true);
        unlink($tmpFile);

        expect($result)->toBeArray()
            ->and($result['site.name'])->toBe('My Blog')
            ->and($result['site.url'])->toBe('https://example.com');
    });
});

describe('flatten', function () {
    it('converts nested array to dot-notation keys', function () {
        $data = [
            'site' => [
                'name' => 'Test',
                'url' => 'https://example.com',
            ],
        ];

        $result = YAML::flatten($data);

        expect($result)->toBeArray()
            ->and($result['site.name'])->toBe('Test')
            ->and($result['site.url'])->toBe('https://example.com');
    });

    it('preserves array values at intermediate nodes', function () {
        $data = [
            'content' => [
                'posts' => [
                    'base' => '/posts',
                ],
            ],
        ];

        $result = YAML::flatten($data);

        // The intermediate 'content' key should hold its original sub-array
        expect($result['content'])->toBeArray()
            ->and($result['content']['posts'])->toBeArray()
            // And the leaf value should also be accessible via dot notation
            ->and($result['content.posts.base'])->toBe('/posts');
    });

    it('handles deeply nested structures with 3+ levels', function () {
        $data = [
            'a' => [
                'b' => [
                    'c' => [
                        'd' => 'deep value',
                    ],
                ],
            ],
        ];

        $result = YAML::flatten($data);

        expect($result['a.b.c.d'])->toBe('deep value')
            ->and($result)->toHaveKey('a')
            ->and($result)->toHaveKey('a.b')
            ->and($result)->toHaveKey('a.b.c')
            ->and($result)->toHaveKey('a.b.c.d');
    });

    it('handles flat input without nesting', function () {
        $data = [
            'name' => 'Test',
            'version' => 2,
        ];

        $result = YAML::flatten($data);

        expect($result['name'])->toBe('Test')
            ->and($result['version'])->toBe(2);
    });
});
