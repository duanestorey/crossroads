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

    // Create two plugins that modify the entry
    $plugin1 = new class ('p1') extends Plugin {
        public function processOne($entry)
        {
            $entry->processed[] = 'plugin1';
            return $entry;
        }
    };

    $plugin2 = new class ('p2') extends Plugin {
        public function processOne($entry)
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

    // Both plugins should have processed the entry (bug fix verification)
    expect($result->processed)->toBe(['plugin1', 'plugin2']);
});

it('chains processAll through all plugins', function () {
    $config = new Config(['site.name' => 'Test']);
    $pm = new PluginManager($config);

    $plugin = new class ('counter') extends Plugin {
        public function processOne($entry)
        {
            $entry->count = ($entry->count ?? 0) + 1;
            return $entry;
        }
    };

    $pm->installPlugin($plugin);

    $entries = [new \stdClass(), new \stdClass()];
    $result = $pm->processAll($entries);

    expect($result)->toHaveCount(2)
        ->and($result[0]->count)->toBe(1)
        ->and($result[1]->count)->toBe(1);
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
        public function templateParamFilter($params)
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
