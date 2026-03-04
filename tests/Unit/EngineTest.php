<?php

use CR\Engine;
use CR\Log;

afterEach(function () {
    Log::instance()->listeners = [];
});

/**
 * Helper: call a private/protected method on an object via reflection.
 */
function callEnginePrivateMethod(object $obj, string $method, array $args = []): mixed
{
    $ref = new ReflectionMethod($obj, $method);
    return $ref->invokeArgs($obj, $args);
}

/**
 * Helper: get a protected/private property value via reflection.
 */
function getEngineProperty(object $obj, string $property): mixed
{
    $ref = new ReflectionProperty($obj, $property);
    return $ref->getValue($obj);
}

describe('_extractFlags', function () {
    it('extracts a single --debug flag from argv', function () {
        $engine = new Engine();

        $argc = 3;
        $argv = ['crossroads', 'build', '--debug'];

        callEnginePrivateMethod($engine, '_extractFlags', [&$argc, &$argv]);

        $flags = getEngineProperty($engine, 'flags');

        expect($flags)->toContain('debug');
    });

    it('extracts multiple flags from argv', function () {
        $engine = new Engine();

        $argc = 4;
        $argv = ['crossroads', 'build', '--debug', '--verbose'];

        callEnginePrivateMethod($engine, '_extractFlags', [&$argc, &$argv]);

        $flags = getEngineProperty($engine, 'flags');

        expect($flags)->toContain('debug')
            ->and($flags)->toContain('verbose');
    });

    it('removes flags from argv and decrements argc', function () {
        $engine = new Engine();

        $argc = 4;
        $argv = ['crossroads', 'build', '--debug', '--verbose'];

        callEnginePrivateMethod($engine, '_extractFlags', [&$argc, &$argv]);

        expect($argc)->toBe(2)
            ->and($argv)->toBe(['crossroads', 'build']);
    });

    it('preserves non-flag arguments in order', function () {
        $engine = new Engine();

        $argc = 5;
        $argv = ['crossroads', '--debug', 'import', 'wordpress', 'https://example.com'];

        callEnginePrivateMethod($engine, '_extractFlags', [&$argc, &$argv]);

        expect($argv)->toBe(['crossroads', 'import', 'wordpress', 'https://example.com'])
            ->and($argc)->toBe(4);
    });

    it('handles argv with no flags', function () {
        $engine = new Engine();

        $argc = 2;
        $argv = ['crossroads', 'build'];

        callEnginePrivateMethod($engine, '_extractFlags', [&$argc, &$argv]);

        $flags = getEngineProperty($engine, 'flags');

        expect($flags)->toBeEmpty()
            ->and($argc)->toBe(2)
            ->and($argv)->toBe(['crossroads', 'build']);
    });
});

describe('_hasFlag', function () {
    it('returns true after a flag has been extracted', function () {
        $engine = new Engine();

        $argc = 3;
        $argv = ['crossroads', 'build', '--debug'];

        callEnginePrivateMethod($engine, '_extractFlags', [&$argc, &$argv]);

        $result = callEnginePrivateMethod($engine, '_hasFlag', ['debug']);

        expect($result)->toBeTrue();
    });

    it('returns false before any flags have been extracted', function () {
        $engine = new Engine();

        $result = callEnginePrivateMethod($engine, '_hasFlag', ['debug']);

        expect($result)->toBeFalse();
    });

    it('returns false for a flag that was not provided', function () {
        $engine = new Engine();

        $argc = 3;
        $argv = ['crossroads', 'build', '--debug'];

        callEnginePrivateMethod($engine, '_extractFlags', [&$argc, &$argv]);

        $result = callEnginePrivateMethod($engine, '_hasFlag', ['nonexistent']);

        expect($result)->toBeFalse();
    });
});

describe('_getAllowableCommands', function () {
    it('returns an array with expected command keys', function () {
        $engine = new Engine();

        $commands = callEnginePrivateMethod($engine, '_getAllowableCommands', []);

        expect($commands)->toBeArray()
            ->and($commands)->toHaveKey('build')
            ->and($commands)->toHaveKey('import')
            ->and($commands)->toHaveKey('serve')
            ->and($commands)->toHaveKey('clean')
            ->and($commands)->toHaveKey('new')
            ->and($commands)->toHaveKey('init')
            ->and($commands)->toHaveKey('upgrade')
            ->and($commands)->toHaveKey('stats')
            ->and($commands)->toHaveKey('db');
    });

    it('maps build to 0 required params', function () {
        $engine = new Engine();
        $commands = callEnginePrivateMethod($engine, '_getAllowableCommands', []);

        expect($commands['build'])->toBe(0);
    });

    it('maps import to 2 required params', function () {
        $engine = new Engine();
        $commands = callEnginePrivateMethod($engine, '_getAllowableCommands', []);

        expect($commands['import'])->toBe(2);
    });

    it('maps new to 1 required param', function () {
        $engine = new Engine();
        $commands = callEnginePrivateMethod($engine, '_getAllowableCommands', []);

        expect($commands['new'])->toBe(1);
    });

    it('maps db to 1 required param', function () {
        $engine = new Engine();
        $commands = callEnginePrivateMethod($engine, '_getAllowableCommands', []);

        expect($commands['db'])->toBe(1);
    });
});
