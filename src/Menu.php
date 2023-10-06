<?php

namespace Blocs;

class Menu
{
    public static function get($name = 'root', $breadcrumbList = [])
    {
        // 設定読み込み
        $configList = config('menu');

        // 追加設定の読み込み
        if (file_exists(config_path('menu.json'))) {
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
        }

        // 指定されたheadline読み込み
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
        $currentPrefix = \Blocs\Common::routePrefix();

        // メニュー、パンクズリスト
        $menuList = [];
        $isActive = false;
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
                list($config['sub'], $subHeadline, $breadcrumbList, $isSubActive) = self::get($config['sub'], $breadcrumbList);

                if ($isSubActive) {
                    // サブメニューでマッチ
                    $config['active'] = true;
                    $isActive = true;
                    false === $headline && $headline = $subHeadline;

                    // パンクズリストに階層を追加
                    array_unshift($breadcrumbList, $config);
                }
            }

            $configNameList = explode('.', $config['name']);
            array_pop($configNameList);
            $configPrefix = implode('.', $configNameList);

            if ($configPrefix === $currentPrefix) {
                $config['active'] = true;
                $isActive = true;
                false === $headline && $headline = $config;

                if ($breadcrumb) {
                    $breadcrumbList = [$config, $breadcrumb];
                } else {
                    $breadcrumbList = [$config];
                    unset($breadcrumbList[0]['url']);
                }
            } elseif (empty($config['active'])) {
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

        // UserRoleをチェック
        $isUserRole = false;
        foreach (\Route::getRoutes() as $route) {
            if ($route->getName() !== $currentName) {
                continue;
            }

            $middlewareList = \Route::gatherRouteMiddleware($route);
            empty($middlewareList) && $middlewareList = [];
            foreach ($middlewareList as $middleware) {
                if (false !== strpos($middleware, '\UserRole')) {
                    $isUserRole = true;
                    break;
                }
            }
            break;
        }
        if (!$isUserRole) {
            // UserRoleがない
            return true;
        }

        // 必要な権限を取得
        $configRole = config('role');
        $roleList = [];
        foreach ($configRole as $roleName => $routeNameList) {
            foreach ($routeNameList as $routePreg) {
                if (preg_match('/'.$routePreg.'/', $currentName)) {
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
}
