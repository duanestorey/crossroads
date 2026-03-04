<?php

use CR\Config;
use CR\Plugin;
use CR\PluginManager;

it('installs plugins', function () {
    $config = new Config(['site.name' => 'Test']);
    $pm = new PluginManager($config);
    $plugin = new Plugin('test');

    $pm->installPlugin($plugin);

    expect($pm->plugins)->toHaveCount(1);
});

it('chains processOne through all plugins', function () {
    $config = new Config(['site.name' => 'Test']);
    $pm = new PluginManager($config);

    $plugin1 = new class ('p1') extends Plugin {
        public function processOne(mixed $entry): mixed
        {
            $entry->processed[] = 'plugin1';
            return $entry;
        }
    };

    $plugin2 = new class ('p2') extends Plugin {
        public function processOne(mixed $entry): mixed
        {
            $entry->processed[] = 'plugin2';
            return $entry;
        }
    };

    $pm->installPlugin($plugin1);
    $pm->installPlugin($plugin2);

    $entry = new \stdClass();
    $entry->processed = [];

    $result = $pm->processOne($entry);

    expect($result->processed)->toBe(['plugin1', 'plugin2']);
});

it('chains processAll through two mutating plugins cumulatively', function () {
    $config = new Config(['site.name' => 'Test']);
    $pm = new PluginManager($config);

    $plugin1 = new class ('adder') extends Plugin {
        public function processOne(mixed $entry): mixed
        {
            $entry->value = ($entry->value ?? 0) + 10;
            return $entry;
        }
    };

    $plugin2 = new class ('doubler') extends Plugin {
        public function processOne(mixed $entry): mixed
        {
            $entry->value = ($entry->value ?? 0) * 2;
            return $entry;
        }
    };

    $pm->installPlugin($plugin1);
    $pm->installPlugin($plugin2);

    $entries = [new \stdClass(), new \stdClass()];
    $result = $pm->processAll($entries);

    // Each entry: 0 + 10 = 10, then 10 * 2 = 20
    expect($result)->toHaveCount(2)
        ->and($result[0]->value)->toBe(20)
        ->and($result[1]->value)->toBe(20);
});

it('returns entry unchanged when no plugins installed', function () {
    $config = new Config(['site.name' => 'Test']);
    $pm = new PluginManager($config);

    $entry = new \stdClass();
    $entry->title = 'Original';

    $result = $pm->processOne($entry);

    expect($result->title)->toBe('Original');
});

it('chains templateParamFilter through all plugins', function () {
    $config = new Config(['site.name' => 'Test']);
    $pm = new PluginManager($config);

    $plugin = new class ('test') extends Plugin {
        public function templateParamFilter(mixed $params): mixed
        {
            $params->filtered = true;
            return $params;
        }
    };

    $pm->installPlugin($plugin);

    $params = new \stdClass();
    $result = $pm->templateParamFilter($params);

    expect($result->filtered)->toBeTrue();
});

it('chains contentFilter through plugins', function () {
    $config = new Config(['site.name' => 'Test']);
    $pm = new PluginManager($config);

    $plugin = new class ('filter') extends Plugin {
        public function contentFilter(mixed $content): mixed
        {
            $content->html = str_replace('foo', 'bar', $content->html);
            return $content;
        }
    };

    $pm->installPlugin($plugin);

    $content = new \stdClass();
    $content->html = 'hello foo world';

    $result = $pm->contentFilter($content);

    expect($result->html)->toBe('hello bar world');
});
