<?php

/**
 * Copyright (C) 2010 LINEAR JAPAN Co., Ltd. All Rights Reserved.
 *
 * This source code or any portion thereof must not be
 * reproduced or used in any manner whatsoever.
 */

namespace Blocs;

class Thumbnail
{
    public static function create($tmpLoc, $p_width, $p_height, $crop = false)
    {
        defined('TEMPLATE_CACHE_DIR') || define('TEMPLATE_CACHE_DIR', config('view.compiled'));

        // サムネイルファイル名を取得
        $thumb_crop = $crop ? '_c' : '';
        $thumb_name = $p_width.'x'.$p_height.$thumb_crop.'-'.basename($tmpLoc);
        $thumbLoc = TEMPLATE_CACHE_DIR.'/'.$thumb_name;

        // サムネイルファイルの拡張子を取得
        $thumb_ext = \File::extension($tmpLoc);

        if (is_file($thumbLoc)) {
            // すでにサムネイルファイルが存在している時
            return $thumbLoc;
        }

        if (!filesize($tmpLoc)) {
            // ファイルサイズが0の時
            return false;
        }

        list($width, $height, $o_width, $o_height) = self::_get_thumbnail_size($tmpLoc, $p_width, $p_height, $crop);
        if ($width === $o_width && $height === $o_height) {
            copy($tmpLoc, $thumbLoc) && chmod($thumbLoc, 0666);

            return $thumbLoc;
        }

        if (!function_exists('imagecreatetruecolor')) {
            return false;
        }

        $o_image = self::_image_create($tmpLoc, $thumb_ext);
        if (!$o_image) {
            return false;
        }

        if ($crop) {
            $image = imagecreatetruecolor($p_width, $p_height);

            // アルファブレンディングを無効
            imagealphablending($image, false);

            // アルファフラグを設定
            imagesavealpha($image, true);

            imagecopyresampled($image, $o_image, 0, 0, intval(($width - $p_width) / 2), intval(($height - $p_height) / 2), $width, $height, $o_width, $o_height);
        } else {
            $image = imagecreatetruecolor($width, $height);

            // アルファブレンディングを無効
            imagealphablending($image, false);

            // アルファフラグを設定
            imagesavealpha($image, true);

            imagecopyresampled($image, $o_image, 0, 0, 0, 0, $width, $height, $o_width, $o_height);
        }

        self::_image_output($image, $thumbLoc, $thumb_ext);

        return $thumbLoc;
    }

    /* Private function */

    private static function _get_thumbnail_size($tmpLoc, $p_width, $p_height, $crop)
    {
        list($width, $height) = list($o_width, $o_height) = getimagesize($tmpLoc);

        if ($crop) {
            // 指定サイズを覆う大きさ
            if (isset($p_width)) {
                $height = $height * $p_width / $width;
                $width = $p_width;
            }
            if (isset($p_height) && $height < $p_height) {
                $width = $width * $p_height / $height;
                $height = $p_height;
            }
        } else {
            // 指定サイズに収まる大きさ
            if (isset($p_width) && $p_width < $width) {
                $height = $height * $p_width / $width;
                $width = $p_width;
            }
            if (isset($p_height) && $p_height < $height) {
                $width = $width * $p_height / $height;
                $height = $p_height;
            }
        }

        return [intval($width), intval($height), $o_width, $o_height];
    }

    private static function _image_create($tmpLoc, $ext)
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

    private static function _image_output($image, $thumbLoc, $thumb_ext)
    {
        switch ($thumb_ext) {
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
            case 'xpm':
                imagexpm($image, $thumbLoc);

                return;
        }
    }
}

/* End of file */
