<?php

namespace Blocs;

class Thumbnail
{
    public static function create($tmpLoc, $pWidth, $pHeight, $crop = false)
    {
        // サムネイルファイル名を生成
        $thumbCrop = $crop ? '_c' : '';
        $thumbName = $pWidth.'x'.$pHeight.$thumbCrop.'-'.basename($tmpLoc);
        $thumbLoc = BLOCS_CACHE_DIR.'/'.$thumbName;

        $thumbExt = strtolower(pathinfo($tmpLoc, PATHINFO_EXTENSION));

        if (is_file($thumbLoc)) {
            return $thumbLoc;
        }

        if (! is_file($tmpLoc) || ! filesize($tmpLoc)) {
            return false;
        }

        [$width, $height, $oWidth, $oHeight] = self::calculateThumbnailDimensions($tmpLoc, $pWidth, $pHeight, $crop);
        if ($oWidth < 1 || $oHeight < 1) {
            return false;
        }

        if ($width === $oWidth && $height === $oHeight) {
            if (! copy($tmpLoc, $thumbLoc)) {
                return false;
            }
            chmod($thumbLoc, 0666);

            return $thumbLoc;
        }

        if (! function_exists('imagecreatetruecolor')) {
            return false;
        }

        // 画素数が大きすぎる画像は、読み込み時のメモリ不足を避けるため縮小しない
        if ($oWidth * $oHeight > self::maxPixels()) {
            return false;
        }

        $oImage = self::createImageResource($tmpLoc, $thumbExt);
        if (! $oImage) {
            return false;
        }

        // HEICが横になる問題に対応する処理
        $exif = function_exists('exif_read_data') ? @exif_read_data($tmpLoc) : false;
        $orientation = is_array($exif) && is_int($exif['Orientation'] ?? null) ? $exif['Orientation'] : 0;
        $rotateAngle = [8 => 90, 3 => 180, 6 => -90][$orientation] ?? 0;
        if ($rotateAngle !== 0) {
            $oImage = imagerotate($oImage, $rotateAngle, 0);
            if (! $oImage) {
                return false;
            }

            if ($rotateAngle !== 180) {
                // 90度回すと縦横が入れ替わるため、入れ替えた大きさで計算し直す
                [$width, $height, $oWidth, $oHeight] = self::calculateThumbnailDimensions($tmpLoc, $pWidth, $pHeight, $crop, $oHeight, $oWidth);
            }
        }

        if ($crop) {
            if ($pWidth < 1 || $pHeight < 1 || $width < 1 || $height < 1) {
                return false;
            }

            $image = imagecreatetruecolor($pWidth, $pHeight);
            imagealphablending($image, false);
            imagesavealpha($image, true);

            $srcWidth = $oWidth * $pWidth / $width;
            $srcHeight = $oHeight * $pHeight / $height;
            $srcX = ($oWidth - $srcWidth) / 2;
            $srcY = ($oHeight - $srcHeight) / 2;

            imagecopyresampled(
                $image,
                $oImage,
                0,
                0,
                intval($srcX),
                intval($srcY),
                $pWidth,
                $pHeight,
                intval($srcWidth),
                intval($srcHeight)
            );
        } else {
            $image = imagecreatetruecolor($width, $height);
            imagealphablending($image, false);
            imagesavealpha($image, true);

            imagecopyresampled($image, $oImage, 0, 0, 0, 0, $width, $height, $oWidth, $oHeight);
        }

        if (! self::outputImageResource($image, $thumbLoc, $thumbExt)) {
            is_file($thumbLoc) && unlink($thumbLoc);

            return false;
        }

        return $thumbLoc;
    }

    private static function calculateThumbnailDimensions($sourcePath, $targetWidth, $targetHeight, $crop, $originalWidth = null, $originalHeight = null)
    {
        if (! isset($originalWidth) || ! isset($originalHeight)) {
            $imageSize = @getimagesize($sourcePath);
            if (! is_array($imageSize) || empty($imageSize[0]) || empty($imageSize[1])) {
                return [0, 0, 0, 0];
            }
            [$originalWidth, $originalHeight] = $imageSize;
        }
        [$width, $height] = [$originalWidth, $originalHeight];

        if ($crop) {
            if (isset($targetWidth)) {
                $height = $height * $targetWidth / $width;
                $width = $targetWidth;
            }
            if (isset($targetHeight) && $height < $targetHeight) {
                $width = $width * $targetHeight / $height;
                $height = $targetHeight;
            }
        } else {
            if (isset($targetWidth) && $targetWidth < $width) {
                $height = $height * $targetWidth / $width;
                $width = $targetWidth;
            }
            if (isset($targetHeight) && $targetHeight < $height) {
                $width = $width * $targetHeight / $height;
                $height = $targetHeight;
            }
        }

        // 極端に細長い画像でも幅・高さが0にならないようにする（0だと画像を作れずエラーになる）
        return [max(1, intval($width)), max(1, intval($height)), $originalWidth, $originalHeight];
    }

    private static function createImageResource($sourcePath, $extension)
    {
        $createFunction = [
            'gif' => 'imagecreatefromgif',
            'jpg' => 'imagecreatefromjpeg',
            'jpeg' => 'imagecreatefromjpeg',
            'png' => 'imagecreatefrompng',
            'webp' => 'imagecreatefromwebp',
            'wbmp' => 'imagecreatefromwbmp',
            'xbm' => 'imagecreatefromxbm',
            'xpm' => 'imagecreatefromxpm',
        ][$extension] ?? null;

        // GDのビルドによっては対応していない形式があるため、関数の有無も確かめる
        if ($createFunction === null || ! function_exists($createFunction)) {
            return false;
        }

        return @$createFunction($sourcePath);
    }

    private static function outputImageResource($image, $thumbLoc, $thumbExt): bool
    {
        $written = match ($thumbExt) {
            'gif' => imagegif($image, $thumbLoc),
            'jpg', 'jpeg' => imagejpeg($image, $thumbLoc, self::definedConstant('ADMIN_IMAGE_JPEG_QUALITY', -1)),
            'png' => imagepng($image, $thumbLoc, self::pngQuality()),
            'webp' => imagewebp($image, $thumbLoc, self::definedConstant('ADMIN_IMAGE_WEBP_QUALITY', -1)),
            'wbmp' => imagewbmp($image, $thumbLoc),
            'xbm' => imagexbm($image, $thumbLoc),
            default => false,
        };

        if (! $written || ! is_file($thumbLoc)) {
            return false;
        }

        chmod($thumbLoc, 0666);

        return true;
    }

    private static function maxPixels(): int
    {
        return (int) self::definedConstant('ADMIN_IMAGE_MAX_PIXELS', 40000000);
    }

    private static function pngQuality(): int
    {
        // PNGの圧縮レベルは -1（既定）〜 9。範囲外の指定は既定に戻す
        $quality = self::definedConstant('ADMIN_IMAGE_PNG_QUALITY', -1);
        if ($quality < -1 || $quality > 9) {
            return -1;
        }

        return (int) $quality;
    }

    /**
     * 定数の値を返す。未定義なら既定値で定義してから返す。
     */
    private static function definedConstant(string $name, int $default)
    {
        defined($name) || define($name, $default);

        return constant($name);
    }
}
