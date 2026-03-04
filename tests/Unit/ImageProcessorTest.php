<?php

use CR\Config;
use CR\ImageProcessor;
use CR\Log;
use CR\LogListener;

afterEach(function () {
    Log::instance()->listeners = [];
});

/**
 * Helper: invoke a private method on an object via reflection.
 */
function callPrivateMethod(object $obj, string $method, array $args = []): mixed
{
    $ref = new ReflectionMethod($obj, $method);

    return $ref->invoke($obj, ...$args);
}

describe('constructor defaults', function () {
    it('sets convertToWebp to false by default', function () {
        $ip = new ImageProcessor(new Config(null));

        expect($ip->convertToWebp)->toBeFalse();
    });

    it('sets generateResponsive to false by default', function () {
        $ip = new ImageProcessor(new Config(null));

        expect($ip->generateResponsive)->toBeFalse();
    });

    it('sets jpegQuality to 82 by default', function () {
        $ip = new ImageProcessor(new Config(null));

        expect($ip->jpegQuality)->toBe(82);
    });

    it('sets webpQuality to 80 by default', function () {
        $ip = new ImageProcessor(new Config(null));

        expect($ip->webpQuality)->toBe(80);
    });

    it('sets pngCompression to 6 by default', function () {
        $ip = new ImageProcessor(new Config(null));

        expect($ip->pngCompression)->toBe(6);
    });

    it('sets avifQuality to 63 by default', function () {
        $ip = new ImageProcessor(new Config(null));

        expect($ip->avifQuality)->toBe(63);
    });

    it('sets default responsive sizes', function () {
        $ip = new ImageProcessor(new Config(null));

        expect($ip->responsiveSizes)->toBe([320, 480, 640, 960, 1360, 1600]);
    });

    it('reads config overrides when provided', function () {
        $config = new Config([
            'options.images.convert_to_webp' => true,
            'options.images.generate_responsive' => true,
            'options.images.jpeg_quality' => 90,
            'options.images.webp_quality' => 70,
            'options.images.png_compression' => 9,
            'options.images.avif_quality' => 50,
            'options.images.responsive_sizes' => [320, 640],
        ]);

        $ip = new ImageProcessor($config);

        expect($ip->convertToWebp)->toBeTrue()
            ->and($ip->generateResponsive)->toBeTrue()
            ->and($ip->jpegQuality)->toBe(90)
            ->and($ip->webpQuality)->toBe(70)
            ->and($ip->pngCompression)->toBe(9)
            ->and($ip->avifQuality)->toBe(50)
            ->and($ip->responsiveSizes)->toBe([320, 640]);
    });
});

describe('_isRemoteImage', function () {
    it('returns true for http URL', function () {
        $ip = new ImageProcessor(new Config(null));

        expect(callPrivateMethod($ip, '_isRemoteImage', ['http://example.com/img.jpg']))->toBeTrue();
    });

    it('returns true for https URL', function () {
        $ip = new ImageProcessor(new Config(null));

        expect(callPrivateMethod($ip, '_isRemoteImage', ['https://example.com/img.jpg']))->toBeTrue();
    });

    it('returns false for absolute local path', function () {
        $ip = new ImageProcessor(new Config(null));

        expect(callPrivateMethod($ip, '_isRemoteImage', ['/local/path/img.jpg']))->toBeFalse();
    });

    it('returns false for relative path', function () {
        $ip = new ImageProcessor(new Config(null));

        expect(callPrivateMethod($ip, '_isRemoteImage', ['relative/img.jpg']))->toBeFalse();
    });

    it('returns false for empty string', function () {
        $ip = new ImageProcessor(new Config(null));

        expect(callPrivateMethod($ip, '_isRemoteImage', ['']))->toBeFalse();
    });
});

describe('_getImageNameForResponsive', function () {
    it('inserts width suffix before .jpg extension', function () {
        $ip = new ImageProcessor(new Config(null));

        $result = callPrivateMethod($ip, '_getImageNameForResponsive', ['/path/image.jpg', 640]);

        expect($result)->toBe('/path/image-640w.jpg');
    });

    it('inserts width suffix before .webp extension', function () {
        $ip = new ImageProcessor(new Config(null));

        $result = callPrivateMethod($ip, '_getImageNameForResponsive', ['/path/image.webp', 320]);

        expect($result)->toBe('/path/image-320w.webp');
    });

    it('inserts width suffix before .png extension', function () {
        $ip = new ImageProcessor(new Config(null));

        $result = callPrivateMethod($ip, '_getImageNameForResponsive', ['/path/photo.png', 960]);

        expect($result)->toBe('/path/photo-960w.png');
    });

    it('handles filename with multiple dots', function () {
        $ip = new ImageProcessor(new Config(null));

        $result = callPrivateMethod($ip, '_getImageNameForResponsive', ['/path/my.photo.jpg', 480]);

        expect($result)->toBe('/path/my.photo-480w.jpg');
    });
});

describe('_getCachedImageSize', function () {
    it('returns false for nonexistent file', function () {
        $ip = new ImageProcessor(new Config(null));

        $result = callPrivateMethod($ip, '_getCachedImageSize', ['/nonexistent/file.png']);

        expect($result)->toBeFalse();
    });

    it('returns image dimensions for a valid image', function () {
        $tmpFile = tempnam(sys_get_temp_dir(), 'cr_test_') . '.png';
        $img = imagecreatetruecolor(10, 10);
        imagepng($img, $tmpFile);
        unset($img);

        $ip = new ImageProcessor(new Config(null));

        $result = callPrivateMethod($ip, '_getCachedImageSize', [$tmpFile]);

        expect($result)->toBeArray()
            ->and($result[0])->toBe(10)
            ->and($result[1])->toBe(10);

        unlink($tmpFile);
    });

    it('caches results on repeated calls', function () {
        $tmpFile = tempnam(sys_get_temp_dir(), 'cr_test_') . '.png';
        $img = imagecreatetruecolor(10, 10);
        imagepng($img, $tmpFile);
        unset($img);

        $ip = new ImageProcessor(new Config(null));

        $result1 = callPrivateMethod($ip, '_getCachedImageSize', [$tmpFile]);
        $result2 = callPrivateMethod($ip, '_getCachedImageSize', [$tmpFile]);

        expect($result1)->toBe($result2)
            ->and($result1[0])->toBe(10)
            ->and($result1[1])->toBe(10);

        unlink($tmpFile);
    });
});

describe('_isValidImage', function () {
    it('returns true for a valid PNG image', function () {
        $tmpFile = tempnam(sys_get_temp_dir(), 'cr_test_') . '.png';
        $img = imagecreatetruecolor(10, 10);
        imagepng($img, $tmpFile);
        unset($img);

        $ip = new ImageProcessor(new Config(null));

        $result = callPrivateMethod($ip, '_isValidImage', [$tmpFile]);

        expect($result)->toBeTrue();

        unlink($tmpFile);
    });

    it('returns true for a valid JPEG image', function () {
        $tmpFile = tempnam(sys_get_temp_dir(), 'cr_test_') . '.jpg';
        $img = imagecreatetruecolor(10, 10);
        imagejpeg($img, $tmpFile);
        unset($img);

        $ip = new ImageProcessor(new Config(null));

        $result = callPrivateMethod($ip, '_isValidImage', [$tmpFile]);

        expect($result)->toBeTrue();

        unlink($tmpFile);
    });

    it('returns false for a nonexistent file', function () {
        $ip = new ImageProcessor(new Config(null));

        $result = callPrivateMethod($ip, '_isValidImage', ['/nonexistent/file.png']);

        expect($result)->toBeFalse();
    });

    it('returns false for a text file with image extension', function () {
        $tmpFile = tempnam(sys_get_temp_dir(), 'cr_test_') . '.png';
        file_put_contents($tmpFile, 'not an image');

        $ip = new ImageProcessor(new Config(null));

        $result = callPrivateMethod($ip, '_isValidImage', [$tmpFile]);

        expect($result)->toBeFalse();

        unlink($tmpFile);
    });
});
