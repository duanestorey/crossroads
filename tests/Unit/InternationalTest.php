<?php

use CR\International;

afterEach(function () {
    // The singleton persists across the entire test run. If we loaded a
    // custom locale file we need to remove its keys so later tests see
    // the original state. We use reflection because $strings is protected.
    $ref = new ReflectionProperty(International::class, 'strings');
    $strings = $ref->getValue(International::instance());

    // Remove any test keys that were injected during this test.
    unset($strings['test.custom.greeting']);
    $ref->setValue(International::instance(), $strings);
});

describe('singleton', function () {
    it('returns the same instance on multiple calls', function () {
        $a = International::instance();
        $b = International::instance();

        expect($a)->toBe($b);
    });
});

describe('get', function () {
    it('returns a known i18n string', function () {
        $result = International::instance()->get('core.app.starting');

        expect($result)->toBeString()
            ->and($result)->toContain('%s');
    });

    it('returns empty string for nonexistent key', function () {
        $result = International::instance()->get('nonexistent.key.here');

        expect($result)->toBe('');
    });
});

describe('_i18n global function', function () {
    it('works the same as get()', function () {
        $fromGet = International::instance()->get('core.app.starting');
        $fromFunc = CR\_i18n('core.app.starting');

        expect($fromFunc)->toBe($fromGet);
    });
});

describe('loadLocaleFile', function () {
    it('adds new strings from a temp YAML file', function () {
        $tmpFile = sys_get_temp_dir() . '/cr_test_locale_' . uniqid() . '.yaml';
        file_put_contents($tmpFile, "test:\n  custom:\n    greeting: \"Hello World\"\n");

        International::instance()->loadLocaleFile($tmpFile);

        expect(International::instance()->get('test.custom.greeting'))->toBe('Hello World');

        unlink($tmpFile);
    });
});
