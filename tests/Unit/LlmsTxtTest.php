<?php

/**
 * Specification tests for llms.txt description sanitization.
 *
 * IMPORTANT: These tests mirror the sanitization logic in Builder::_writeLlmsTxt()
 * (crossroads-core/src/Builder.php, lines ~255-262) rather than calling it directly,
 * because Builder requires full build infrastructure (config, entries, theme, etc.).
 *
 * If the sanitization logic in Builder changes, these tests must be updated to match.
 *
 * @see \CR\Builder::_writeLlmsTxt()
 */

it('collapses multi-line descriptions to single line', function () {
    $description = "This is a long description\nthat spans multiple lines\nand should be collapsed";

    $descText = trim(preg_replace('/\s+/', ' ', $description));

    expect($descText)->not->toContain("\n")
        ->and($descText)->toBe('This is a long description that spans multiple lines and should be collapsed');
});

it('truncates descriptions longer than 200 characters', function () {
    $description = str_repeat('word ', 100); // 500 chars

    $descText = trim(preg_replace('/\s+/', ' ', $description));
    if (mb_strlen($descText) > 200) {
        $descText = mb_substr($descText, 0, 200) . '...';
    }

    expect(mb_strlen($descText))->toBeLessThanOrEqual(203) // 200 + '...'
        ->and($descText)->toEndWith('...');
});

it('leaves short descriptions unchanged', function () {
    $description = 'A short description';

    $descText = trim(preg_replace('/\s+/', ' ', $description));
    if (mb_strlen($descText) > 200) {
        $descText = mb_substr($descText, 0, 200) . '...';
    }

    expect($descText)->toBe('A short description');
});

it('formats list items correctly with sanitized descriptions', function () {
    $entries = [
        (object) [
            'title' => 'Test Post',
            'url' => 'https://example.com/posts/test-post.html',
            'description' => "A multi-line\ndescription here",
            'isDraft' => false,
        ],
        (object) [
            'title' => 'Another Post',
            'url' => 'https://example.com/posts/another.html',
            'description' => '',
            'isDraft' => false,
        ],
    ];

    $lines = [];
    foreach ($entries as $entry) {
        if ($entry->isDraft) {
            continue;
        }

        $mdUrl = $entry->url . '.md';
        $desc = '';
        if ($entry->description) {
            $descText = trim(preg_replace('/\s+/', ' ', $entry->description));
            if (mb_strlen($descText) > 200) {
                $descText = mb_substr($descText, 0, 200) . '...';
            }
            $desc = ': ' . $descText;
        }
        $lines[] = '- [' . $entry->title . '](' . $mdUrl . ')' . $desc;
    }

    // All lines within H2 sections must be list items
    foreach ($lines as $line) {
        expect($line)->toStartWith('- ');
    }

    // First entry's description should be collapsed to single line
    expect($lines[0])->not->toContain("\n")
        ->and($lines[0])->toContain(': A multi-line description here');

    // Second entry should have no description
    expect($lines[1])->toBe('- [Another Post](https://example.com/posts/another.html.md)');
});

it('excludes draft entries', function () {
    $entries = [
        (object) [
            'title' => 'Published',
            'url' => 'https://example.com/posts/published.html',
            'description' => 'A published post',
            'isDraft' => false,
        ],
        (object) [
            'title' => 'Draft Post',
            'url' => 'https://example.com/posts/draft.html',
            'description' => 'A draft post',
            'isDraft' => true,
        ],
    ];

    $lines = [];
    foreach ($entries as $entry) {
        if ($entry->isDraft) {
            continue;
        }

        $mdUrl = $entry->url . '.md';
        $desc = '';
        if ($entry->description) {
            $descText = trim(preg_replace('/\s+/', ' ', $entry->description));
            if (mb_strlen($descText) > 200) {
                $descText = mb_substr($descText, 0, 200) . '...';
            }
            $desc = ': ' . $descText;
        }
        $lines[] = '- [' . $entry->title . '](' . $mdUrl . ')' . $desc;
    }

    expect($lines)->toHaveCount(1)
        ->and($lines[0])->toContain('Published');
});

it('handles descriptions with tabs and mixed whitespace', function () {
    $description = "First part\t\tsecond part\n\n  third part";

    $descText = trim(preg_replace('/\s+/', ' ', $description));

    expect($descText)->toBe('First part second part third part')
        ->and($descText)->not->toContain("\t")
        ->and($descText)->not->toContain("\n");
});
