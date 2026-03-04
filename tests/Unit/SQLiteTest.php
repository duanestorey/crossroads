<?php

use CR\Config;
use CR\SQLite;

beforeEach(function () {
    if (!file_exists(CROSSROADS_DB_DIR)) {
        @mkdir(CROSSROADS_DB_DIR, 0755, true);
    }

    $dbFile = CROSSROADS_DB_DIR . '/db.sqlite';
    if (file_exists($dbFile)) {
        @unlink($dbFile);
    }

    $this->config = new Config([
        'site.url' => 'https://example.com',
    ]);

    $this->sqlite = new SQLite($this->config);
});

afterEach(function () {
    unset($this->sqlite);

    $dbFile = CROSSROADS_DB_DIR . '/db.sqlite';
    if (file_exists($dbFile)) {
        @unlink($dbFile);
    }
});

describe('constructor', function () {
    it('creates the SQLite database file', function () {
        expect(file_exists(CROSSROADS_DB_DIR . '/db.sqlite'))->toBeTrue();
    });

    it('enables foreign key enforcement', function () {
        $result = $this->sqlite->query('PRAGMA foreign_keys');

        expect($result)->not->toBeFalse();

        $row = $result->fetchArray(SQLITE3_ASSOC);

        expect($row['foreign_keys'])->toBe(1);
    });
});

describe('rebuild', function () {
    it('creates content, taxonomy, and images tables', function () {
        $this->sqlite->rebuild();

        $tables = [];
        $result = $this->sqlite->query("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'");
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $tables[] = $row['name'];
        }

        expect($tables)->toContain('content')
            ->and($tables)->toContain('taxonomy')
            ->and($tables)->toContain('images');
    });
});

describe('prepare', function () {
    it('returns SQLite3Stmt on valid SQL', function () {
        $this->sqlite->rebuild();

        $stmt = $this->sqlite->prepare('SELECT * FROM content WHERE id = :id');

        expect($stmt)->toBeInstanceOf(\SQLite3Stmt::class);
    });
});

describe('query', function () {
    it('returns SQLite3Result on valid query', function () {
        $this->sqlite->rebuild();

        $result = $this->sqlite->query('SELECT * FROM content');

        expect($result)->toBeInstanceOf(\SQLite3Result::class);
    });
});

describe('getLastRowID', function () {
    it('returns incremented ID after inserts', function () {
        $this->sqlite->rebuild();

        $this->sqlite->query("INSERT INTO content (title, slug, type, hash) VALUES ('First', 'first', 'posts', 'hash1')");
        $firstId = $this->sqlite->getLastRowID();

        $this->sqlite->query("INSERT INTO content (title, slug, type, hash) VALUES ('Second', 'second', 'posts', 'hash2')");
        $secondId = $this->sqlite->getLastRowID();

        expect($firstId)->toBe(1)
            ->and($secondId)->toBe(2)
            ->and($secondId)->toBeGreaterThan($firstId);
    });
});
