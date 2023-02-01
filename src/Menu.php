<?php

namespace Blocs;

class Menu
{
    public static function get($name, $breadcrumbList = [])
    {
        // 設定読み込み
        $configList = config('menu');

        // 指定されたhedline読み込み
        if (isset($configList['headline'])) {
            $headline = $configList['headline'];
            config(['menu.headline' => null]);
        } else {
            $headline = false;
        }

        $breadcrumb = isset($configList['breadcrumb']) ? $configList['breadcrumb'] : false;

        if (!isset($configList[$name])) {
            return [[], [], []];
        }
        $configList = $configList[$name];

        // ルート名を取得
        $currentName = \Route::currentRouteName();
        empty($currentName) || list($currentName) = explode('.', $currentName, 2);

        // メニュー、パンクズリスト
        $menuList = [];
        foreach ($configList as $config) {
            if (!isset($config['url'])) {
                if (empty($config['argv'])) {
                    $config['url'] = route($config['name']);
                } else {
                    $config['url'] = route($config['name'], $config['argv']);
                }
            }
            isset($config['label']) || $config['label'] = \Blocs\Lang::get($config['lang']);

            if (isset($config['sub'])) {
                list($config['sub'], $subHeadline, $breadcrumbList) = self::get($config['sub'], $breadcrumbList);

                if (!empty($breadcrumbList)) {
                    // サブメニューでマッチ
                    $config['active'] = true;
                    false === $headline && $headline = $subHeadline;

                    // パンクズリストに階層を追加
                    array_unshift($breadcrumbList, $config);
                }
            }

            list($configName) = explode('.', $config['name'], 2);
            if ($configName === $currentName) {
                $config['active'] = true;
                false === $headline && $headline = $config;

                if ($breadcrumb) {
                    $breadcrumbList = [$config, $breadcrumb];
                } else {
                    $breadcrumbList = [$config];
                    unset($breadcrumbList[0]['url']);
                }
            } else {
                $config['active'] = false;
            }

            // パンクズリストはメニューには表示しない
            if (!empty($config['breadcrumb'])) {
                continue;
            }

            // 権限があるかチェック
            if (!self::checkRole($config['name'])) {
                continue;
            }

            $menuList[] = $config;
        }

        return [$menuList, $headline, $breadcrumbList];
    }

    public static function headline($icon, $lang)
    {
        config(['menu.headline' => [
            'icon' => $icon,
            'label' => \Blocs\Lang::get($lang),
        ]]);
    }

    public static function breadcrumb($lang)
    {
        config(['menu.breadcrumb' => [
            'label' => \Blocs\Lang::get($lang),
        ]]);
    }

    public static function checkRole($currentName = null)
    {
        isset($currentName) || $currentName = \Route::currentRouteName();

        // 必要な権限を取得
        $configRole = config('role');
        $roleList = [];
        foreach ($configRole as $roleName => $routeNameList) {
            foreach ($routeNameList as $routeName) {
                if (false !== strpos($currentName, $routeName)) {
                    $roleList[] = $roleName;
                    break;
                }
            }
        }

        if (empty($roleList)) {
            return true;
        }

        // 自分の権限を取得
        $_userData = \Auth::user();
        $myRoleList = explode("\t", $_userData['role']);

        foreach ($myRoleList as $myRole) {
            if (in_array($myRole, $roleList)) {
                return true;
            }
        }

        return false;
    }
}
