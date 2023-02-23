<?php

namespace App\Consts;

class Blocs
{
    public static function define()
    {
        // 言語設定
        defined('BLOCS_LOCALE') || define('BLOCS_LOCALE', 'ja');

        // テンプレートのキャッシュを保存するディレクトリ
        defined('BLOCS_CACHE_DIR') || define('BLOCS_CACHE_DIR', config('view.compiled'));

        // テンプレートのルートディレクトリ
        $viewPathList = config('view.paths');
        defined('BLOCS_ROOT_DIR') || define('BLOCS_ROOT_DIR', $viewPathList[0]);

        // optionをつなぐ文字列
        defined('BLOCS_OPTION_SEPARATOR') || define('BLOCS_OPTION_SEPARATOR', ', ');

        // includeの上限設定
        defined('BLOCS_INCLUDE_MAX') || define('BLOCS_INCLUDE_MAX', 20);

        // 管理画面のビュー
        defined('ADMIN_VIEW_PREFIX') || define('ADMIN_VIEW_PREFIX', 'admin');
    }
}
