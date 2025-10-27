<?php

namespace Blocs;

class Thumbnail
{
    public static function create($tmpLoc, $pWidth, $pHeight, $crop = false)
    {
        // サムネイルファイル名を取得
        $thumbCrop = $crop ? '_c' : '';
        $thumbName = $pWidth.'x'.$pHeight.$thumbCrop.'-'.basename($tmpLoc);
        $thumbLoc = BLOCS_CACHE_DIR.'/'.$thumbName;

        // サムネイルファイルの拡張子を取得
        $thumbExt = \File::extension($tmpLoc);

        if (is_file($thumbLoc)) {
            // すでにサムネイルファイルが存在している時
            return $thumbLoc;
        }

        if (! filesize($tmpLoc)) {
            // ファイルサイズが0の時
            return false;
        }

        [$width, $height, $oWidth, $oHeight] = self::getThumbnailSize($tmpLoc, $pWidth, $pHeight, $crop);
        if ($width === $oWidth && $height === $oHeight) {
            copy($tmpLoc, $thumbLoc) && chmod($thumbLoc, 0666);

            return $thumbLoc;
        }

        if (! function_exists('imagecreatetruecolor')) {
            return false;
        }

        $oImage = self::imageCreate($tmpLoc, $thumbExt);
        if (! $oImage) {
            return false;
        }

        // HEICが横になる問題に対応
        $exif = @exif_read_data($tmpLoc);
        if (! empty($exif['Orientation'])) {
            switch ($exif['Orientation']) {
                case 8:
                    $oImage = imagerotate($oImage, 90, 0);
                    [$width, $height, $oWidth, $oHeight] = self::getThumbnailSize($tmpLoc, $pWidth, $pHeight, $crop, $oHeight, $oWidth);
                    break;
                case 3:
                    $oImage = imagerotate($oImage, 180, 0);
                    break;
                case 6:
                    $oImage = imagerotate($oImage, -90, 0);
                    [$width, $height, $oWidth, $oHeight] = self::getThumbnailSize($tmpLoc, $pWidth, $pHeight, $crop, $oHeight, $oWidth);
                    break;
            }
        }

        if ($crop) {
            $image = imagecreatetruecolor($pWidth, $pHeight);

            // アルファブレンディングを無効
            imagealphablending($image, false);

            // アルファフラグを設定
            imagesavealpha($image, true);

            imagecopyresampled($image, $oImage, 0, 0, intval(($width - $pWidth) / 2), intval(($height - $pHeight) / 2), $width, $height, $oWidth, $oHeight);
        } else {
            $image = imagecreatetruecolor($width, $height);

            // アルファブレンディングを無効
            imagealphablending($image, false);

            // アルファフラグを設定
            imagesavealpha($image, true);

            imagecopyresampled($image, $oImage, 0, 0, 0, 0, $width, $height, $oWidth, $oHeight);
        }

        self::imageOutput($image, $thumbLoc, $thumbExt);

        return $thumbLoc;
    }

    private static function getThumbnailSize($tmpLoc, $pWidth, $pHeight, $crop, $oWidth = null, $oHeight = null)
    {
        if (! isset($oWidth) || ! isset($oHeight)) {
            [$oWidth, $oHeight] = @getimagesize($tmpLoc);
        }
        [$width, $height] = [$oWidth, $oHeight];

        if ($crop) {
            // 指定サイズを覆う大きさ
            if (isset($pWidth)) {
                $height = $height * $pWidth / $width;
                $width = $pWidth;
            }
            if (isset($pHeight) && $height < $pHeight) {
                $width = $width * $pHeight / $height;
                $height = $pHeight;
            }
        } else {
            // 指定サイズに収まる大きさ
            if (isset($pWidth) && $pWidth < $width) {
                $height = $height * $pWidth / $width;
                $width = $pWidth;
            }
            if (isset($pHeight) && $pHeight < $height) {
                $width = $width * $pHeight / $height;
                $height = $pHeight;
            }
        }

        return [intval($width), intval($height), $oWidth, $oHeight];
    }

    private static function imageCreate($tmpLoc, $ext)
    {
        switch ($ext) {
            case 'gif':
                return @imagecreatefromgif($tmpLoc);
            case 'jpg':
                return @imagecreatefromjpeg($tmpLoc);
            case 'jpeg':
                return @imagecreatefromjpeg($tmpLoc);
            case 'png':
                return @imagecreatefrompng($tmpLoc);
            case 'webp':
                return @imagecreatefromwebp($tmpLoc);
            case 'wbmp':
                return @imagecreatefromwbmp($tmpLoc);
            case 'xbm':
                return @imagecreatefromxbm($tmpLoc);
            case 'xpm':
                return @imagecreatefromxpm($tmpLoc);
            default:
                return false;
        }
    }

    private static function imageOutput($image, $thumbLoc, $thumbExt)
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
