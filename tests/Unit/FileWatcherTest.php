<?php

use CR\FileWatcher;

/**
 * Recursively remove a directory and its contents.
 */
function removeDir(string $dir): void
{
    if (!is_dir($dir)) {
        return;
    }

    $entries = scandir($dir);
    foreach ($entries as $entry) {
        if ($entry === '.' || $entry === '..') {
            continue;
        }
        $path = $dir . '/' . $entry;
        if (is_dir($path)) {
            removeDir($path);
        } else {
            @unlink($path);
        }
    }
    @rmdir($dir);
}

beforeEach(function () {
    $this->tmpDir = sys_get_temp_dir() . '/cr_watcher_test_' . uniqid();
    mkdir($this->tmpDir, 0755, true);
});

afterEach(function () {
    removeDir($this->tmpDir);
});

describe('check', function () {
    it('returns empty array when no changes occurred', function () {
        file_put_contents($this->tmpDir . '/file.md', '# Hello');
        $watcher = new FileWatcher([$this->tmpDir]);

        $changes = $watcher->check();

        expect($changes)->toBe([]);
    });

    it('detects an added file', function () {
        file_put_contents($this->tmpDir . '/existing.md', '# Existing');
        $watcher = new FileWatcher([$this->tmpDir]);

        file_put_contents($this->tmpDir . '/new-file.md', '# New');

        $changes = $watcher->check();
        $types = array_column($changes, 'type');
        $paths = array_column($changes, 'path');

        expect($types)->toContain('added')
            ->and($paths)->toContain($this->tmpDir . '/new-file.md');
    });

    it('detects a modified file', function () {
        $file = $this->tmpDir . '/modify-me.md';
        file_put_contents($file, '# Original');
        $watcher = new FileWatcher([$this->tmpDir]);

        // Set mtime into the future so the change is detected
        touch($file, time() + 10);

        $changes = $watcher->check();
        $types = array_column($changes, 'type');
        $paths = array_column($changes, 'path');

        expect($types)->toContain('modified')
            ->and($paths)->toContain($file);
    });

    it('detects a deleted file', function () {
        $file = $this->tmpDir . '/delete-me.md';
        file_put_contents($file, '# Gone soon');
        $watcher = new FileWatcher([$this->tmpDir]);

        unlink($file);

        $changes = $watcher->check();
        $types = array_column($changes, 'type');
        $paths = array_column($changes, 'path');

        expect($types)->toContain('deleted')
            ->and($paths)->toContain($file);
    });

    it('ignores files with untracked extensions', function () {
        file_put_contents($this->tmpDir . '/readme.txt', 'Hello');
        $watcher = new FileWatcher([$this->tmpDir]);

        // Create another .txt file — should still be ignored
        file_put_contents($this->tmpDir . '/notes.txt', 'Notes');

        $changes = $watcher->check();

        expect($changes)->toBe([]);
    });

    it('skips vendor directories', function () {
        $vendorDir = $this->tmpDir . '/vendor';
        mkdir($vendorDir);
        file_put_contents($vendorDir . '/lib.md', '# Vendor file');
        $watcher = new FileWatcher([$this->tmpDir]);

        // Add another file inside vendor — should not be detected
        file_put_contents($vendorDir . '/new-lib.md', '# New vendor file');

        $changes = $watcher->check();

        expect($changes)->toBe([]);
    });
});
