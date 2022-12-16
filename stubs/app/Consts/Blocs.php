<?php

namespace App\Consts;

class Blocs
{
    public static function define()
    {
        // テンプレートのキャッシュを保存するディレクトリ
        define('BLOCS_CACHE_DIR', config('view.compiled'));

        // テンプレートのルートディレクトリ
        $viewPathList = config('view.paths');
        define('BLOCS_ROOT_DIR', $viewPathList[0]);

        // optionをつなぐ文字列
        define('BLOCS_OPTION_SEPARATOR', ', ');

        // includeの上限設定
        define('BLOCS_INCLUDE_MAX', 20);
    }
}
