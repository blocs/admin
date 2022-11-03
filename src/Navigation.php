<?php

namespace Blocs;

use Illuminate\Support\Facades\Route;

class Navigation
{
    public static function get($name, $breadcrumbs = [])
    {
        // 設定読み込み
        $configs = config('navigation');

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
            if (!empty($config['breadcrumb'])) {
                continue;
            }

            // 権限があるかチェック
            if (!self::check_group($config['name'])) {
                continue;
            }

            $navigations[] = $config;
        }

        return [$navigations, $headline, $breadcrumbs];
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

    public static function check_group($current_name = null)
    {
        isset($current_name) || $current_name = Route::currentRouteName();

        // 必要な権限を取得
        $config_group = config('group');
        $groups = [];
        foreach ($config_group as $group_name => $route_names) {
            foreach ($route_names as $route_name) {
                if (false !== strpos($current_name, $route_name)) {
                    $groups[] = $group_name;
                    break;
                }
            }
        }

        if (empty($groups)) {
            return true;
        }

        // 自分の権限を取得
        $_user_data = \Auth::user();
        $my_groups = explode("\t", $_user_data['group']);

        foreach ($my_groups as $my_group) {
            if (in_array($my_group, $groups)) {
                return true;
            }
        }

        return false;
    }
}
