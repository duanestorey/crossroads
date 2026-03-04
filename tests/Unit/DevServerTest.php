<?php

use CR\Config;
use CR\DB;
use CR\DevServer;
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

/**
 * Helper: call a protected method on DevServer via reflection.
 */
function callDevServerMethod(object $obj, string $method, array $args = []): mixed
{
    $ref = new ReflectionMethod($obj, $method);
    return $ref->invokeArgs($obj, $args);
}

/**
 * Helper: create a DevServer instance with minimal dependencies.
 */
function createTestDevServer(): DevServer
{
    if (!file_exists(CROSSROADS_DB_DIR)) {
        @mkdir(CROSSROADS_DB_DIR, 0755, true);
    }

    $config = new Config(null);
    $pluginManager = new PluginManager($config);
    $db = new DB($config);

    return new DevServer($config, $pluginManager, $db);
}

describe('_findAvailablePort', function () {
    it('returns a port number greater than zero', function () {
        $server = createTestDevServer();
        $port = callDevServerMethod($server, '_findAvailablePort');

        expect($port)->toBeGreaterThan(0);
    });

    it('returns a port number less than 65536', function () {
        $server = createTestDevServer();
        $port = callDevServerMethod($server, '_findAvailablePort');

        expect($port)->toBeLessThan(65536);
    });

    it('returns different ports on successive calls', function () {
        $server = createTestDevServer();
        $port1 = callDevServerMethod($server, '_findAvailablePort');
        $port2 = callDevServerMethod($server, '_findAvailablePort');

        expect($port1)->not->toBe($port2);
    });
});

describe('constructor', function () {
    it('creates a DevServer instance', function () {
        $server = createTestDevServer();

        expect($server)->toBeInstanceOf(DevServer::class);
    });
});
