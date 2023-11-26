<?php

namespace Blocs;

class Menu
{
    public static function get($name = 'root', $breadcrumbList = [])
    {
        // 設定読み込み
        $configList = self::getJson(config('menu'));

        // 指定されたheadline読み込み
        if (isset($configList['headline'])) {
            $headline = $configList['headline'];
            config(['menu.headline' => null]);
        } else {
            $headline = false;
        }

        // 指定されたbreadcrumbを追加
        $breadcrumb = isset($configList['breadcrumb']) ? $configList['breadcrumb'] : false;

        if (!isset($configList[$name])) {
            return [[], [], []];
        }
        $configList = $configList[$name];

        // メニュー、パンクズリスト
        $menuList = [];
        $isActive = false;
        foreach ($configList as $config) {
            if (!isset($config['url']) && empty($config['breadcrumb'])) {
                if (empty($config['argv'])) {
                    $config['url'] = route($config['name']);
                } else {
                    $config['url'] = route($config['name'], $config['argv']);
                }
            }
            isset($config['label']) || $config['label'] = lang($config['lang']);

            $config['active'] = false;
            if (isset($config['sub'])) {
                list($config['sub'], $subHeadline, $breadcrumbList, $isSubActive) = self::get($config['sub'], $breadcrumbList);

                if ($isSubActive) {
                    // サブメニューがactive
                    $config['active'] = true;
                    $isActive = true;
                    false === $headline && $headline = $subHeadline;

                    // パンクズリストに階層を追加
                    array_unshift($breadcrumbList, $config);
                }
            }

            // メニューがactive
            if (self::checkActive($config)) {
                $config['active'] = true;
                $isActive = true;
                false === $headline && $headline = $config;

                if (empty($breadcrumbList)) {
                    if ($breadcrumb) {
                        $breadcrumbList = [$config, $breadcrumb];
                    } else {
                        $breadcrumbList = [$config];
                        unset($breadcrumbList[0]['url']);
                    }
                }
            }

            // パンクズリストはメニューには表示しない
            if (!empty($config['breadcrumb'])) {
                continue;
            }

            // 権限があるかチェック
            if (empty($config['role']) && !self::checkRole($config['name'])) {
                continue;
            }

            $guard = isset($config['guard']) ? $config['guard'] : 'web';
            if (!\Auth::guard($guard)->check()) {
                continue;
            }

            $menuList[] = $config;
        }

        return [$menuList, $headline, $breadcrumbList, $isActive];
    }

    public static function headline($icon, $lang)
    {
        config(['menu.headline' => [
            'icon' => $icon,
            'label' => lang($lang),
        ]]);
    }

    public static function breadcrumb($lang)
    {
        config(['menu.breadcrumb' => [
            'label' => lang($lang),
        ]]);
    }

    public static function checkRole($currentName = null)
    {
        isset($currentName) || $currentName = \Route::currentRouteName();

        // 必要な権限を取得
        $configRole = config('role');
        $roleList = [];
        foreach ($configRole as $roleName => $routeNameList) {
            foreach ($routeNameList as $routePreg) {
                if (preg_match('/^'.$routePreg.'/', $currentName)) {
                    $roleList[] = $roleName;
                    break;
                }
            }
        }

        // 自分の権限を取得
        $_userData = \Auth::user();
        $myRoleList = empty($_userData['role']) ? [] : explode("\t", $_userData['role']);

        foreach ($myRoleList as $myRole) {
            if (in_array($myRole, $roleList)) {
                return true;
            }
        }

        return false;
    }

    private static function getJson($configList)
    {
        if (!file_exists(config_path('menu.json'))) {
            return $configList;
        }

        $configJson = json_decode(file_get_contents(config_path('menu.json')), true);

        foreach ($configJson as $menuName => $config) {
            if (empty($configList[$menuName])) {
                $configList[$menuName] = $config;
                continue;
            }

            $menuNameList = [];
            foreach ($configList[$menuName] as $menu) {
                $menuNameList[] = $menu['name'];
            }

            foreach ($config as $menu) {
                in_array($menu['name'], $menuNameList) || $configList[$menuName][] = $menu;
            }
        }

        return $configList;
    }

    private static function checkActive($config)
    {
        if (!empty($config['breadcrumb'])) {
            // パンくずは完全一致
            $currentName = \Route::currentRouteName();

            return $config['name'] === $currentName;
        }

        // メニューはメソッド以外一致
        $currentPrefix = prefix();

        $configNameList = explode('.', $config['name']);
        array_pop($configNameList);
        $configPrefix = implode('.', $configNameList);

        return $configPrefix === $currentPrefix;
    }
}
