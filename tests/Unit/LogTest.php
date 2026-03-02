<?php

use CR\Log;
use CR\LogListener;

afterEach(function () {
    // Clear all listeners to prevent state leaking between tests.
    // The singleton persists across the entire test run, so we must
    // reset its mutable state after every test.
    Log::instance()->listeners = [];
});

/**
 * Helper: create a test listener that records all log calls.
 */
function createTestListener(): LogListener
{
    return new class () extends LogListener {
        public array $calls = [];
        public int $currentLevel = Log::INFO;

        public function setLevel($level): void
        {
            $this->currentLevel = $level;
        }

        public function log($message, $tabs, $level): void
        {
            $this->calls[] = [
                'message' => $message,
                'tabs' => $tabs,
                'level' => $level,
            ];
        }
    };
}

describe('singleton', function () {
    it('returns the same instance on repeated calls', function () {
        $a = Log::instance();
        $b = Log::instance();

        expect($a)->toBe($b);
    });
});

describe('installListener', function () {
    it('adds a listener to the listeners array', function () {
        $listener = createTestListener();
        Log::instance()->installListener($listener);

        expect(Log::instance()->listeners)->toHaveCount(1)
            ->and(Log::instance()->listeners[0])->toBe($listener);
    });

    it('supports multiple listeners', function () {
        $listener1 = createTestListener();
        $listener2 = createTestListener();

        Log::instance()->installListener($listener1);
        Log::instance()->installListener($listener2);

        expect(Log::instance()->listeners)->toHaveCount(2);
    });
});

describe('log', function () {
    it('dispatches message to all installed listeners', function () {
        $listener1 = createTestListener();
        $listener2 = createTestListener();

        Log::instance()->installListener($listener1);
        Log::instance()->installListener($listener2);

        Log::instance()->log('test message');

        expect($listener1->calls)->toHaveCount(1)
            ->and($listener2->calls)->toHaveCount(1);
    });

    it('does nothing with no listeners installed (no error)', function () {
        // Should not throw or produce errors
        Log::instance()->log('orphan message', 0, Log::WARNING);

        expect(Log::instance()->listeners)->toHaveCount(0);
    });

    it('passes correct message, tabs, and level to listeners', function () {
        $listener = createTestListener();
        Log::instance()->installListener($listener);

        Log::instance()->log('detailed message', 2, Log::ERROR);

        expect($listener->calls)->toHaveCount(1)
            ->and($listener->calls[0]['message'])->toBe('detailed message')
            ->and($listener->calls[0]['tabs'])->toBe(2)
            ->and($listener->calls[0]['level'])->toBe(Log::ERROR);
    });

    it('uses default values for tabs and level', function () {
        $listener = createTestListener();
        Log::instance()->installListener($listener);

        Log::instance()->log('default params');

        expect($listener->calls[0]['tabs'])->toBe(0)
            ->and($listener->calls[0]['level'])->toBe(Log::INFO);
    });
});

describe('LOG global function', function () {
    it('dispatches to the singleton instance', function () {
        $listener = createTestListener();
        Log::instance()->installListener($listener);

        \CR\LOG('global message');

        expect($listener->calls)->toHaveCount(1)
            ->and($listener->calls[0]['message'])->toBe('global message');
    });

    it('passes tabs and level through to listeners', function () {
        $listener = createTestListener();
        Log::instance()->installListener($listener);

        \CR\LOG('warning message', 3, Log::WARNING);

        expect($listener->calls[0]['message'])->toBe('warning message')
            ->and($listener->calls[0]['tabs'])->toBe(3)
            ->and($listener->calls[0]['level'])->toBe(Log::WARNING);
    });
});

describe('log levels', function () {
    it('defines expected severity constants', function () {
        expect(Log::DEBUG)->toBe(0)
            ->and(Log::INFO)->toBe(1)
            ->and(Log::WARNING)->toBe(2)
            ->and(Log::ERROR)->toBe(3)
            ->and(Log::FATAL)->toBe(10);
    });
});
