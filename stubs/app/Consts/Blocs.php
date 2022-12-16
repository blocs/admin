<?php

namespace App\Consts;

class Blocs
{
    public static function define()
    {
        // テンプレートのキャッシュを保存するディレクトリ
        define('TEMPLATE_CACHE_DIR', config('view.compiled'));

        // テンプレートのルートディレクトリ
        $viewPathList = config('view.paths');
        define('TEMPLATE_ROOT_DIR', $viewPathList[0]);

        // optionをつなぐ文字列
        define('TEMPLATE_OPTION_SEPARATOR', ', ');

        // includeの上限設定
        define('TEMPLATE_INCLUDE_MAX', 20);
    }
}
