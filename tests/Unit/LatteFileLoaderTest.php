<?php

use CR\LatteFileLoader;

beforeEach(function () {
    $this->tmpDir1 = sys_get_temp_dir() . '/cr_latte_test1_' . uniqid();
    $this->tmpDir2 = sys_get_temp_dir() . '/cr_latte_test2_' . uniqid();
    mkdir($this->tmpDir1);
    mkdir($this->tmpDir2);

    $this->loader = new LatteFileLoader();
});

afterEach(function () {
    // Remove any files in temp dirs, then the dirs themselves
    foreach ([$this->tmpDir1, $this->tmpDir2] as $dir) {
        if (is_dir($dir)) {
            $files = glob($dir . '/*');
            foreach ($files as $file) {
                @unlink($file);
            }
            @rmdir($dir);
        }
    }
});

describe('setDirectories', function () {
    it('stores directories accessible via reflection', function () {
        $this->loader->setDirectories([$this->tmpDir1, $this->tmpDir2]);

        $ref = new ReflectionProperty(LatteFileLoader::class, 'templateDirs');

        expect($ref->getValue($this->loader))->toBe([$this->tmpDir1, $this->tmpDir2]);
    });
});

describe('getContent', function () {
    it('returns file content from first matching directory', function () {
        file_put_contents($this->tmpDir1 . '/test.latte', '<h1>Hello</h1>');
        $this->loader->setDirectories([$this->tmpDir1, $this->tmpDir2]);

        $content = $this->loader->getContent('test.latte');

        expect($content)->toBe('<h1>Hello</h1>');
    });

    it('finds file in second directory when not in first', function () {
        file_put_contents($this->tmpDir2 . '/only-in-second.latte', '<p>Second dir</p>');
        $this->loader->setDirectories([$this->tmpDir1, $this->tmpDir2]);

        $content = $this->loader->getContent('only-in-second.latte');

        expect($content)->toBe('<p>Second dir</p>');
    });

    it('throws RuntimeException for nonexistent file', function () {
        $this->loader->setDirectories([$this->tmpDir1, $this->tmpDir2]);

        expect(fn () => $this->loader->getContent('missing.latte'))
            ->toThrow(\Latte\RuntimeException::class, "Missing template file 'missing.latte'.");
    });

    it('gives first directory precedence when file exists in both', function () {
        file_put_contents($this->tmpDir1 . '/shared.latte', 'From first');
        file_put_contents($this->tmpDir2 . '/shared.latte', 'From second');
        $this->loader->setDirectories([$this->tmpDir1, $this->tmpDir2]);

        $content = $this->loader->getContent('shared.latte');

        expect($content)->toBe('From first');
    });
});
