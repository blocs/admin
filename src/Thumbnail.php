<?php

namespace Blocs;

use Illuminate\Support\Facades\File;

class Thumbnail
{
    public static function create($tmpLoc, $pWidth, $pHeight, $crop = false)
    {
        // サムネイルファイル名を生成
        $thumbCrop = $crop ? '_c' : '';
        $thumbName = $pWidth.'x'.$pHeight.$thumbCrop.'-'.basename($tmpLoc);
        $thumbLoc = BLOCS_CACHE_DIR.'/'.$thumbName;

        // サムネイルファイルの拡張子を判別
        $thumbExt = File::extension($tmpLoc);

        if (is_file($thumbLoc)) {
            // 既存のサムネイルファイルがある場合はそのまま返却
            return $thumbLoc;
        }

        if (! filesize($tmpLoc)) {
            // ファイルサイズが0の時は処理を中止
            return false;
        }

        [$width, $height, $oWidth, $oHeight] = self::calculateThumbnailDimensions($tmpLoc, $pWidth, $pHeight, $crop);
        if ($width === $oWidth && $height === $oHeight) {
            copy($tmpLoc, $thumbLoc) && chmod($thumbLoc, 0666);

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
            $image = imagecreatetruecolor($pWidth, $pHeight);

            // アルファブレンディングを無効化
            imagealphablending($image, false);

            // アルファフラグを設定
            imagesavealpha($image, true);

            imagecopyresampled($image, $oImage, 0, 0, intval(($width - $pWidth) / 2), intval(($height - $pHeight) / 2), $width, $height, $oWidth, $oHeight);
        } else {
            $image = imagecreatetruecolor($width, $height);

            // アルファブレンディングを無効化
            imagealphablending($image, false);

            // アルファフラグを設定
            imagesavealpha($image, true);

            imagecopyresampled($image, $oImage, 0, 0, 0, 0, $width, $height, $oWidth, $oHeight);
        }

        self::outputImageResource($image, $thumbLoc, $thumbExt);

        return $thumbLoc;
    }

    private static function calculateThumbnailDimensions($sourcePath, $targetWidth, $targetHeight, $crop, $originalWidth = null, $originalHeight = null)
    {
        if (! isset($originalWidth) || ! isset($originalHeight)) {
            [$originalWidth, $originalHeight] = @getimagesize($sourcePath);
        }
        [$width, $height] = [$originalWidth, $originalHeight];

        if ($crop) {
            // 指定サイズを覆う大きさに調整
            if (isset($targetWidth)) {
                $height = $height * $targetWidth / $width;
                $width = $targetWidth;
            }
            if (isset($targetHeight) && $height < $targetHeight) {
                $width = $width * $targetHeight / $height;
                $height = $targetHeight;
            }
        } else {
            // 指定サイズに収まる大きさに調整
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

    private static function outputImageResource($image, $thumbLoc, $thumbExt)
    {
        switch ($thumbExt) {
            case 'gif':
                imagegif($image, $thumbLoc);

                return;
            case 'jpg':
                defined('ADMIN_IMAGE_JPEG_QUALITY') || define('ADMIN_IMAGE_JPEG_QUALITY', -1);
                imagejpeg($image, $thumbLoc, ADMIN_IMAGE_JPEG_QUALITY);

                return;
            case 'jpeg':
                defined('ADMIN_IMAGE_JPEG_QUALITY') || define('ADMIN_IMAGE_JPEG_QUALITY', -1);
                imagejpeg($image, $thumbLoc, ADMIN_IMAGE_JPEG_QUALITY);

                return;
            case 'png':
                defined('ADMIN_IMAGE_PNG_QUALITY') || define('ADMIN_IMAGE_PNG_QUALITY', -1);
                imagepng($image, $thumbLoc, ADMIN_IMAGE_PNG_QUALITY);

                return;
            case 'webp':
                defined('ADMIN_IMAGE_WEBP_QUALITY') || define('ADMIN_IMAGE_WEBP_QUALITY', -1);
                imagepng($image, $thumbLoc, ADMIN_IMAGE_WEBP_QUALITY);

                return;
            case 'wbmp':
                imagewbmp($image, $thumbLoc);

                return;
            case 'xbm':
                imagexbm($image, $thumbLoc);

                return;
        }
    }
}
