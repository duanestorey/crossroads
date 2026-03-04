<?php

use CR\Builder;
use CR\Config;
use CR\DB;
use CR\Log;
use CR\PluginManager;

afterEach(function () {
    Log::instance()->listeners = [];

    // Clean up any test database files
    $dbFile = CROSSROADS_DB_DIR . '/db.sqlite';
    if (file_exists($dbFile)) {
        @unlink($dbFile);
    }
});

describe('constructor', function () {
    it('can be constructed with valid dependencies', function () {
        if (!file_exists(CROSSROADS_DB_DIR)) {
            @mkdir(CROSSROADS_DB_DIR, 0755, true);
        }

        $config = new Config(['site.name' => 'Test Site']);
        $pluginManager = new PluginManager($config);
        $db = new DB($config);

        $builder = new Builder($config, $pluginManager, $db);

        expect($builder)->toBeInstanceOf(Builder::class);
    });

    it('stores the config reference', function () {
        if (!file_exists(CROSSROADS_DB_DIR)) {
            @mkdir(CROSSROADS_DB_DIR, 0755, true);
        }

        $config = new Config(['site.name' => 'Test Site']);
        $pluginManager = new PluginManager($config);
        $db = new DB($config);

        $builder = new Builder($config, $pluginManager, $db);

        expect($builder->config)->toBe($config);
    });

    it('stores the plugin manager reference', function () {
        if (!file_exists(CROSSROADS_DB_DIR)) {
            @mkdir(CROSSROADS_DB_DIR, 0755, true);
        }

        $config = new Config(['site.name' => 'Test Site']);
        $pluginManager = new PluginManager($config);
        $db = new DB($config);

        $builder = new Builder($config, $pluginManager, $db);

        expect($builder->pluginManager)->toBe($pluginManager);
    });

    it('initializes totalPages to zero', function () {
        if (!file_exists(CROSSROADS_DB_DIR)) {
            @mkdir(CROSSROADS_DB_DIR, 0755, true);
        }

        $config = new Config(['site.name' => 'Test Site']);
        $pluginManager = new PluginManager($config);
        $db = new DB($config);

        $builder = new Builder($config, $pluginManager, $db);

        expect($builder->totalPages)->toBe(0);
    });

    it('has a run method', function () {
        expect(method_exists(Builder::class, 'run'))->toBeTrue();
    });
});
