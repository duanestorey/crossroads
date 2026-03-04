<?php

use CR\Builder;

describe('sanitizeDescription', function () {
    it('collapses multi-line descriptions to single line', function () {
        $result = Builder::sanitizeDescription("This is a long description\nthat spans multiple lines\nand should be collapsed");

        expect($result)->not->toContain("\n")
            ->and($result)->toBe('This is a long description that spans multiple lines and should be collapsed');
    });

    it('truncates descriptions longer than 200 characters', function () {
        $result = Builder::sanitizeDescription(str_repeat('word ', 100));

        expect(mb_strlen($result))->toBeLessThanOrEqual(203) // 200 + '...'
            ->and($result)->toEndWith('...');
    });

    it('leaves short descriptions unchanged', function () {
        $result = Builder::sanitizeDescription('A short description');

        expect($result)->toBe('A short description');
    });

    it('handles descriptions with tabs and mixed whitespace', function () {
        $result = Builder::sanitizeDescription("First part\t\tsecond part\n\n  third part");

        expect($result)->toBe('First part second part third part')
            ->and($result)->not->toContain("\t")
            ->and($result)->not->toContain("\n");
    });

    it('handles empty string', function () {
        $result = Builder::sanitizeDescription('');

        expect($result)->toBe('');
    });

    it('trims leading and trailing whitespace', function () {
        $result = Builder::sanitizeDescription('  padded description  ');

        expect($result)->toBe('padded description');
    });
});
