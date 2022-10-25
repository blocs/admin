<?php

namespace Blocs;

use Illuminate\Support\Facades\Route;

class Navigation
{
    public static function get($name = '', $breadcrumbs = [])
    {
        // 設定読み込み
        $configs = config('navigation');

        if (isset($configs['name'])) {
            $name = $configs['name'];
            config(['navigation.name' => null]);
        }

        // 指定されたhedline読み込み
        if (isset($configs['headline'])) {
            $headline = $configs['headline'];
            config(['navigation.headline' => null]);
        } else {
            $headline = false;
        }

        $breadcrumb = isset($configs['breadcrumb']) ? $configs['breadcrumb'] : false;

        if (!isset($configs[$name])) {
            return [[], [], []];
        }
        $configs = $configs[$name];

        // ルート名を取得
        $current_name = Route::currentRouteName();
        empty($current_name) || list($current_name) = explode('.', $current_name, 2);

        // ナビゲーション、パンクズリスト
        $navigations = [];
        foreach ($configs as $config) {
            isset($config['url']) || $config['url'] = route($config['name']);
            isset($config['label']) || $config['label'] = \Lang::get($config['lang']);

            if (isset($config['sub'])) {
                list($config['sub'], $sub_headline, $breadcrumbs) = self::get($config['sub'], $breadcrumbs);

                if (!empty($breadcrumbs)) {
                    // サブメニューでマッチ
                    $config['active'] = true;
                    false === $headline && $headline = $sub_headline;

                    // パンクズリストに階層を追加
                    array_unshift($breadcrumbs, $config);
                }
            }

            list($config_name) = explode('.', $config['name'], 2);
            if ($config_name === $current_name) {
                $config['active'] = true;
                false === $headline && $headline = $config;

                if ($breadcrumb) {
                    $breadcrumbs = [$config, $breadcrumb];
                } else {
                    $breadcrumbs = [$config];
                    unset($breadcrumbs[0]['url']);
                }
            }

            // パンクズリストはナビゲーションには表示しない
            empty($config['breadcrumb']) && $navigations[] = $config;
        }

        return [$navigations, $headline, $breadcrumbs];
    }

    public static function name($name)
    {
        config(['navigation.name' => $name]);
    }

    public static function headline($icon, $lang)
    {
        config(['navigation.headline' => [
            'icon' => $icon,
            'label' => \Lang::get($lang),
        ]]);
    }

    public static function breadcrumb($lang)
    {
        config(['navigation.breadcrumb' => [
            'label' => \Lang::get($lang),
        ]]);
    }
}
