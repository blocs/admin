<?php

namespace Blocs;

class Menu
{
    private static $headline;
    private static $breadcrumbList = [];

    public static function get($name = 'root', $maxChild = 1)
    {
        // 設定読み込み
        $configList = self::getJson(config('menu'));

        if (!isset($configList[$name])) {
            return [[], [], []];
        }
        $configList = $configList[$name];

        // メニュー、パンクズリスト
        $subMenuList = [];
        $isActive = false;
        foreach ($configList as $config) {
            // ラベル
            isset($config['label']) || $config['label'] = lang($config['lang']);

            // リンク先
            if (!isset($config['url']) && empty($config['breadcrumb']) && isset($config['name'])) {
                if (empty($config['argv'])) {
                    $config['url'] = route($config['name']);
                } else {
                    $config['url'] = route($config['name'], $config['argv']);
                }
            }

            // サブメニュー
            // $maxChild: サブメニューの最大の深さ
            if (isset($config['sub'])) {
                list($config['sub'], $buff, $buff, $isSubActive, $child) = self::get($config['sub'], $maxChild);
                $child > $maxChild && $maxChild = $child;
            } else {
                $isSubActive = false;
            }
            $config['child'] = $maxChild;

            // sub が空配列の場合表示しない
            if (isset($config['sub']) && empty($config['sub'])) {
                continue;
            }

            // メニューかサブメニューがactive
            if ((isset($config['name']) && self::checkActive($config)) || $isSubActive) {
                $config['active'] = true;
                $isActive = true;

                // headlineを設定
                empty(self::$headline) && self::$headline = $config;

                if (empty(self::$breadcrumbList)) {
                    // パンクズリストの最後
                    self::$breadcrumbList = [$config];
                    unset(self::$breadcrumbList[0]['url']);
                } else {
                    // パンクズリストに階層を追加
                    array_unshift(self::$breadcrumbList, $config);
                }
            } else {
                $config['active'] = false;
            }

            // パンクズリストはメニューには表示しない
            if (!empty($config['breadcrumb'])) {
                continue;
            }

            // 権限があるかチェック
            if (empty($config['role']) && isset($config['name']) && !self::checkRole($config['name'])) {
                continue;
            }

            if (isset($config['guard']) && !\Auth::guard($config['guard'])->check()) {
                continue;
            }

            // パンくずには、headlineにメニュー表示
            if (!empty(self::$headline) && !empty(self::$headline['breadcrumb']) && $config['active']) {
                empty(self::$headline['menu']) && self::$headline['menu'] = $config['label'];
            }

            $subMenuList[] = $config;
        }

        return [$subMenuList, self::$headline, self::$breadcrumbList, $isActive, $maxChild + 1];
    }

    public static function headline($icon, $lang)
    {
        // 指定されたheadlineを設定
        self::$headline = [
            'icon' => $icon,
            'label' => lang($lang),
        ];
    }

    public static function breadcrumb($lang)
    {
        // 指定されたbreadcrumbを設定
        self::$breadcrumbList[] = [
            'label' => lang($lang),
        ];
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
                isset($menu['name']) && $menuNameList[] = $menu['name'];
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
