<?php

namespace Blocs;

use Symfony\Component\Mime\MimeTypes;

class Thumbnail
{
    public static function create($tmpLoc, $pWidth, $pHeight, $crop = false)
    {
        // サムネイルファイル名を取得
        $thumbCrop = $crop ? '_c' : '';
        $thumbName = $pWidth.'x'.$pHeight.$thumbCrop.'-'.basename($tmpLoc);
        $thumbLoc = BLOCS_CACHE_DIR.'/'.$thumbName;

        // サムネイルファイルの拡張子を取得
        $thumbExt = self::extension(file_get_contents($tmpLoc));

        if (is_file($thumbLoc)) {
            // すでにサムネイルファイルが存在している時
            return $thumbLoc;
        }

        if (!filesize($tmpLoc)) {
            // ファイルサイズが0の時
            return false;
        }

        list($width, $height, $oWidth, $oHeight) = self::getThumbnailSize($tmpLoc, $pWidth, $pHeight, $crop);
        if ($width === $oWidth && $height === $oHeight) {
            copy($tmpLoc, $thumbLoc) && chmod($thumbLoc, 0666);

            return $thumbLoc;
        }

        if (!function_exists('imagecreatetruecolor')) {
            return false;
        }

        $oImage = self::imageCreate($tmpLoc, $thumbExt);
        if (!$oImage) {
            return false;
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

    private static function getThumbnailSize($tmpLoc, $pWidth, $pHeight, $crop)
    {
        list($width, $height) = list($oWidth, $oHeight) = getimagesize($tmpLoc);

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
                imagejpeg($image, $thumbLoc);

                return;
            case 'jpeg':
                imagejpeg($image, $thumbLoc);

                return;
            case 'png':
                imagepng($image, $thumbLoc);

                return;
            case 'wbmp':
                imagewbmp($image, $thumbLoc);

                return;
            case 'xbm':
                imagexbm($image, $thumbLoc);

                return;
        }
    }

    // 拡張子を取得
    public static function extension($content): string
    {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime_type = finfo_buffer($finfo, $content);
        finfo_close($finfo);

        $mimeTypes = new MimeTypes();
        $extensions = $mimeTypes->getExtensions($mime_type);

        return isset($extensions[0]) ? $extensions[0] : '';
    }
}
