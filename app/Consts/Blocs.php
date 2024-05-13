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
        define('BLOCS_CACHE_DIR', config('view.compiled'));

        // テンプレートのルートディレクトリ
        $viewPathList = config('view.paths');
        define('BLOCS_ROOT_DIR', $viewPathList[0]);

        // optionをつなぐ文字列
        define('BLOCS_OPTION_SEPARATOR', ', ');

        // includeの上限設定
        define('BLOCS_INCLUDE_MAX', 20);

        // 管理画面のビュー
        define('ADMIN_VIEW_PREFIX', 'admin');

        // 管理画面のログイン後の遷移先
        define('ADMIN_LOGIN_REDIRECT_TO', '/home');

        // 管理画面のログアウト後の遷移先
        define('ADMIN_LOGOUT_REDIRECT_TO', '/login');

        // サムネイルの品質
        define('ADMIN_IMAGE_JPEG_QUALITY', -1);
        define('ADMIN_IMAGE_PNG_QUALITY', -1);
    }
}
