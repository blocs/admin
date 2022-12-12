<?php

namespace Blocs;

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
        $currentName = \Route::currentRouteName();
        empty($currentName) || list($currentName) = explode('.', $currentName, 2);

        // ナビゲーション、パンクズリスト
        $navigations = [];
        foreach ($configs as $config) {
            isset($config['url']) || $config['url'] = route($config['name']);
            isset($config['label']) || $config['label'] = \Blocs\Lang::get($config['lang']);

            if (isset($config['sub'])) {
                list($config['sub'], $subHeadline, $breadcrumbs) = self::get($config['sub'], $breadcrumbs);

                if (!empty($breadcrumbs)) {
                    // サブメニューでマッチ
                    $config['active'] = true;
                    false === $headline && $headline = $subHeadline;

                    // パンクズリストに階層を追加
                    array_unshift($breadcrumbs, $config);
                }
            }

            list($configName) = explode('.', $config['name'], 2);
            if ($configName === $currentName) {
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
            'label' => \Blocs\Lang::get($lang),
        ]]);
    }

    public static function breadcrumb($lang)
    {
        config(['navigation.breadcrumb' => [
            'label' => \Blocs\Lang::get($lang),
        ]]);
    }

    public static function check_group($currentName = null)
    {
        isset($currentName) || $currentName = \Route::currentRouteName();

        // 必要な権限を取得
        $configGroup = config('group');
        $groups = [];
        foreach ($configGroup as $groupName => $routeNames) {
            foreach ($routeNames as $routeName) {
                if (false !== strpos($currentName, $routeName)) {
                    $groups[] = $groupName;
                    break;
                }
            }
        }

        if (empty($groups)) {
            return true;
        }

        // 自分の権限を取得
        $_userData = \Auth::user();
        $myGroups = explode("\t", $_userData['group']);

        foreach ($myGroups as $myGroup) {
            if (in_array($myGroup, $groups)) {
                return true;
            }
        }

        return false;
    }
}
