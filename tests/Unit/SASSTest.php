<?php

use CR\SASS;

describe('isSassFile', function () {
    it('returns true for .scss extension', function () {
        expect(SASS::isSassFile('style.scss'))->toBeTrue();
    });

    it('returns true for .sass extension', function () {
        expect(SASS::isSassFile('style.sass'))->toBeTrue();
    });

    it('returns false for .css extension', function () {
        expect(SASS::isSassFile('style.css'))->toBeFalse();
    });

    it('returns false for filename with no extension', function () {
        expect(SASS::isSassFile('no-extension'))->toBeFalse();
    });

    it('returns true for .scss file with path', function () {
        expect(SASS::isSassFile('path/to/file.scss'))->toBeTrue();
    });
});

describe('parseFile', function () {
    it('compiles a valid scss file and returns CSS string', function () {
        $tmpFile = sys_get_temp_dir() . '/cr_test_' . uniqid() . '.scss';
        file_put_contents($tmpFile, 'body { color: red; }');

        $result = SASS::parseFile($tmpFile);

        expect($result)->toBeString()
            ->and($result)->toContain('color: red');

        unlink($tmpFile);
    });

    it('returns false for a nonexistent file', function () {
        $result = SASS::parseFile('/nonexistent/path/to/file.scss');

        expect($result)->toBeFalse();
    });
});
