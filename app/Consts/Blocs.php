<?php

namespace App\Consts;

class Blocs
{
    public static function define()
    {
        // 言語設定
        defined('BLOCS_LOCALE') || define('BLOCS_LOCALE', 'ja');
        defined('BLOCS_TIMEZONE') || define('BLOCS_TIMEZONE', 'Asia/Tokyo');

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

        // 管理画面のログイン後の遷移先
        defined('ADMIN_LOGIN_REDIRECT_TO') || define('ADMIN_LOGIN_REDIRECT_TO', '/home');

        // 管理画面のログアウト後の遷移先
        defined('ADMIN_LOGOUT_REDIRECT_TO') || define('ADMIN_LOGOUT_REDIRECT_TO', '/login');

        // サムネイルの品質
        defined('ADMIN_IMAGE_JPEG_QUALITY') || define('ADMIN_IMAGE_JPEG_QUALITY', -1);
        defined('ADMIN_IMAGE_PNG_QUALITY') || define('ADMIN_IMAGE_PNG_QUALITY', -1);
    }
}
