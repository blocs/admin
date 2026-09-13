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

        if (! filesize($tmpLoc)) {
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

        $oImage = self::createImageResource($tmpLoc, $thumbExt);
        if (! $oImage) {
            return false;
        }

        // HEICが横になる問題に対応する処理
        $exif = @exif_read_data($tmpLoc);
        if (! empty($exif['Orientation'])) {
            switch ($exif['Orientation']) {
                case 8:
                    $oImage = imagerotate($oImage, 90, 0);
                    [$width, $height, $oWidth, $oHeight] = self::calculateThumbnailDimensions($tmpLoc, $pWidth, $pHeight, $crop, $oHeight, $oWidth);
                    break;
                case 3:
                    $oImage = imagerotate($oImage, 180, 0);
                    break;
                case 6:
                    $oImage = imagerotate($oImage, -90, 0);
                    [$width, $height, $oWidth, $oHeight] = self::calculateThumbnailDimensions($tmpLoc, $pWidth, $pHeight, $crop, $oHeight, $oWidth);
                    break;
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

        return [intval($width), intval($height), $originalWidth, $originalHeight];
    }

    private static function createImageResource($sourcePath, $extension)
    {
        switch ($extension) {
            case 'gif':
                return @imagecreatefromgif($sourcePath);
            case 'jpg':
                return @imagecreatefromjpeg($sourcePath);
            case 'jpeg':
                return @imagecreatefromjpeg($sourcePath);
            case 'png':
                return @imagecreatefrompng($sourcePath);
            case 'webp':
                return @imagecreatefromwebp($sourcePath);
            case 'wbmp':
                return @imagecreatefromwbmp($sourcePath);
            case 'xbm':
                return @imagecreatefromxbm($sourcePath);
            case 'xpm':
                return @imagecreatefromxpm($sourcePath);
            default:
                return false;
        }
    }

    private static function outputImageResource($image, $thumbLoc, $thumbExt): bool
    {
        $written = false;

        switch ($thumbExt) {
            case 'gif':
                $written = imagegif($image, $thumbLoc);
                break;
            case 'jpg':
            case 'jpeg':
                defined('ADMIN_IMAGE_JPEG_QUALITY') || define('ADMIN_IMAGE_JPEG_QUALITY', -1);
                $written = imagejpeg($image, $thumbLoc, ADMIN_IMAGE_JPEG_QUALITY);
                break;
            case 'png':
                $written = imagepng($image, $thumbLoc, self::pngQuality());
                break;
            case 'webp':
                defined('ADMIN_IMAGE_WEBP_QUALITY') || define('ADMIN_IMAGE_WEBP_QUALITY', -1);
                $written = imagewebp($image, $thumbLoc, ADMIN_IMAGE_WEBP_QUALITY);
                break;
            case 'wbmp':
                $written = imagewbmp($image, $thumbLoc);
                break;
            case 'xbm':
                $written = imagexbm($image, $thumbLoc);
                break;
            default:
                return false;
        }

        if (! $written || ! is_file($thumbLoc)) {
            return false;
        }

        chmod($thumbLoc, 0666);

        return true;
    }

    private static function pngQuality(): int
    {
        defined('ADMIN_IMAGE_PNG_QUALITY') || define('ADMIN_IMAGE_PNG_QUALITY', -1);
        $quality = ADMIN_IMAGE_PNG_QUALITY;
        if ($quality < -1 || $quality > 9) {
            return -1;
        }

        return (int) $quality;
    }
}
