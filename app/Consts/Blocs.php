<?php

namespace App\Consts;

class Blocs
{
    public static function define()
    {
        // 言語設定
        config(['app.locale' => 'ja']);
        config(['app.timezone' => 'Asia/Tokyo']);
        date_default_timezone_set('Asia/Tokyo');

        // テンプレートのキャッシュを保存するディレクトリ
        defined('BLOCS_CACHE_DIR') || define('BLOCS_CACHE_DIR', config('view.compiled'));

        // テンプレートのルートディレクトリ
        $viewPathList = config('view.paths');
        defined('BLOCS_ROOT_DIR') || define('BLOCS_ROOT_DIR', $viewPathList[0]);

        // optionをつなぐ文字列
        defined('BLOCS_OPTION_SEPARATOR') || define('BLOCS_OPTION_SEPARATOR', ', ');

        // includeの上限設定
        defined('BLOCS_INCLUDE_MAX') || define('BLOCS_INCLUDE_MAX', 50);

        // サムネイルの品質
        defined('ADMIN_IMAGE_JPEG_QUALITY') || define('ADMIN_IMAGE_JPEG_QUALITY', -1);
        defined('ADMIN_IMAGE_PNG_QUALITY') || define('ADMIN_IMAGE_PNG_QUALITY', -1);

        // autoincludeのディレクトリ
        $GLOBALS['BLOCS_AUTOINCLUDE_DIR'] = resource_path('views/admin/autoinclude');
    }
}
