<?php

use CR\Config;
use CR\Log;
use CR\Upgrade;

afterEach(function () {
    Log::instance()->listeners = [];
});

describe('constructor', function () {
    it('stores the config reference', function () {
        $config = new Config(['site.name' => 'Test']);
        $upgrade = new Upgrade($config);

        expect($upgrade->config)->toBe($config);
    });
});

describe('public properties', function () {
    it('has releasesApi pointing to the GitHub API', function () {
        $config = new Config(null);
        $upgrade = new Upgrade($config);

        expect($upgrade->releasesApi)->toContain('api.github.com')
            ->and($upgrade->releasesApi)->toContain('duanestorey/crossroads')
            ->and($upgrade->releasesApi)->toContain('releases/latest');
    });

    it('has releaseZipUrl with a %s placeholder for the tag', function () {
        $config = new Config(null);
        $upgrade = new Upgrade($config);

        expect($upgrade->releaseZipUrl)->toContain('github.com')
            ->and($upgrade->releaseZipUrl)->toContain('duanestorey/crossroads')
            ->and($upgrade->releaseZipUrl)->toContain('%s');
    });

    it('formats releaseZipUrl correctly with sprintf', function () {
        $config = new Config(null);
        $upgrade = new Upgrade($config);

        $formatted = sprintf($upgrade->releaseZipUrl, 'v2.0.0');

        expect($formatted)->toContain('v2.0.0')
            ->and($formatted)->not->toContain('%s');
    });
});

describe('version_compare behavior used by Upgrade', function () {
    it('identifies a newer version correctly', function () {
        expect(version_compare('2.1.0', '2.0.0'))->toBe(1);
    });

    it('identifies an older version correctly', function () {
        expect(version_compare('1.9.0', '2.0.0'))->toBe(-1);
    });

    it('identifies equal versions correctly', function () {
        expect(version_compare('2.0.0', '2.0.0'))->toBe(0);
    });

    it('handles patch-level differences', function () {
        expect(version_compare('2.0.1', '2.0.0'))->toBe(1);
    });
});
