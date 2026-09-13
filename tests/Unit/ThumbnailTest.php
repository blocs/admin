<?php

namespace Blocs\Tests;

use Blocs\Thumbnail;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class ThumbnailTest extends TestCase
{
    /** @var array<int, string> */
    private array $tempFiles = [];

    protected function tearDown(): void
    {
        foreach ($this->tempFiles as $tempFile) {
            is_file($tempFile) && unlink($tempFile);
        }
        $this->tempFiles = [];

        parent::tearDown();
    }

    #[Test]
    public function test_webp_thumbnail_writes_riff_webp_bytes(): void
    {
        if (! function_exists('imagewebp') || ! function_exists('imagecreatetruecolor')) {
            $this->markTestSkipped('GD WebP is not available');
        }

        $source = $this->createImageFile(80, 80, 'webp');
        $thumbnail = Thumbnail::create($source, 40, 40);

        $this->assertIsString($thumbnail);
        $this->assertFileExists($thumbnail);
        $this->tempFiles[] = $thumbnail;

        $header = (string) file_get_contents($thumbnail, false, null, 0, 12);
        $this->assertSame('RIFF', substr($header, 0, 4));
        $this->assertSame('WEBP', substr($header, 8, 4));
    }

    #[Test]
    public function test_uppercase_extension_creates_thumbnail(): void
    {
        if (! function_exists('imagecreatetruecolor')) {
            $this->markTestSkipped('GD is not available');
        }

        $source = $this->createImageFile(80, 80, 'png');
        $upper = $this->tempFile('.PNG');
        $this->assertTrue(copy($source, $upper));

        $thumbnail = Thumbnail::create($upper, 40, 40);

        $this->assertIsString($thumbnail);
        $this->assertFileExists($thumbnail);
        $this->tempFiles[] = $thumbnail;
        $size = getimagesize($thumbnail);
        $this->assertSame(40, $size[0]);
        $this->assertSame(40, $size[1]);
    }

    #[Test]
    public function test_returns_false_when_source_is_not_an_image(): void
    {
        $source = $this->tempFile('.png');
        file_put_contents($source, 'not an image');

        $this->assertFalse(Thumbnail::create($source, 40, 40));
    }

    #[Test]
    public function test_crop_writes_requested_canvas_size(): void
    {
        if (! function_exists('imagecreatetruecolor')) {
            $this->markTestSkipped('GD is not available');
        }

        $source = $this->createImageFile(200, 100, 'png');
        $thumbnail = Thumbnail::create($source, 50, 50, true);

        $this->assertIsString($thumbnail);
        $this->tempFiles[] = $thumbnail;
        $size = getimagesize($thumbnail);
        $this->assertSame(50, $size[0]);
        $this->assertSame(50, $size[1]);
    }

    #[Test, RunInSeparateProcess]
    public function test_out_of_range_png_quality_does_not_throw(): void
    {
        if (! function_exists('imagepng') || ! function_exists('imagecreatetruecolor')) {
            $this->markTestSkipped('GD PNG is not available');
        }

        if (! defined('BLOCS_CACHE_DIR')) {
            $cacheDir = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.'blocs-admin'.DIRECTORY_SEPARATOR;
            is_dir($cacheDir) || mkdir($cacheDir, 0777, true);
            define('BLOCS_CACHE_DIR', $cacheDir);
        }

        define('ADMIN_IMAGE_PNG_QUALITY', 80);

        $source = $this->createImageFile(80, 80, 'png');
        $thumbnail = Thumbnail::create($source, 40, 40);

        $this->assertIsString($thumbnail);
        $this->assertFileExists($thumbnail);
        $this->tempFiles[] = $thumbnail;
    }

    private function createImageFile(int $width, int $height, string $extension): string
    {
        $image = imagecreatetruecolor($width, $height);
        $color = imagecolorallocate($image, 255, 0, 0);
        imagefill($image, 0, 0, $color);

        $path = $this->tempFile('.'.$extension);
        match ($extension) {
            'webp' => imagewebp($image, $path),
            default => imagepng($image, $path),
        };

        return $path;
    }

    private function tempFile(string $suffix): string
    {
        $path = tempnam(sys_get_temp_dir(), 'thumb').$suffix;
        $this->tempFiles[] = $path;

        return $path;
    }
}
