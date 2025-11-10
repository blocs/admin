<?php

namespace Blocs;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

class Menu
{
    private static $headline;

    private static $breadcrumbList = [];

    private static $activePrefix;

    public static function get($name = 'root', $maxChild = 1)
    {
        // 設定の読み込み
        $menuConfigMap = config('menu');

        if (! isset($menuConfigMap[$name])) {
            return [[], [], []];
        }
        $menuCandidates = $menuConfigMap[$name];

        // メニュー一覧とパンくずリストの構築
        $menuList = [];
        $hasActiveMenu = false;
        $currentMaxChild = $maxChild;

        foreach ($menuCandidates as $menuConfig) {
            // ラベルの解決
            $menuConfig = self::resolveLabel($menuConfig);

            // リンク先の解決
            $menuConfig = self::resolveUrl($menuConfig);

            // サブメニューの構築
            [$menuConfig, $isSubActive, $currentMaxChild, $shouldDisplay] = self::resolveSubmenu($menuConfig, $currentMaxChild, $maxChild);

            // sub が空配列の場合は表示しない
            if (! $shouldDisplay) {
                continue;
            }

            // メニューかサブメニューがactive
            if ((isset($menuConfig['name']) && self::isMatchingActivePrefix($menuConfig)) || $isSubActive) {
                $menuConfig['active'] = true;
                $hasActiveMenu = true;

                // headlineを設定
                if (empty(self::$headline)) {
                    self::$headline = $menuConfig;
                }

                if (empty(self::$breadcrumbList)) {
                    // パンクズリストの最後
                    self::$breadcrumbList = [$menuConfig];
                    unset(self::$breadcrumbList[0]['url']);
                } else {
                    // パンクズリストに階層を追加
                    array_unshift(self::$breadcrumbList, $menuConfig);
                }
            } else {
                $menuConfig['active'] = false;
            }

            // 権限があるかチェック
            if (! self::canDisplayMenu($menuConfig)) {
                continue;
            }

            $menuList[] = $menuConfig;
        }

        return [$menuList, self::$headline, self::$breadcrumbList, $hasActiveMenu, $currentMaxChild + 1];
    }

    public static function headline($icon, $lang, $activePrefix = null, $menu = null)
    {
        // 指定されたheadlineを設定
        self::$headline = [
            'icon' => $icon,
            'label' => lang($lang),
            'menu' => $menu ?? '',
        ];

        // 指定されたbreadcrumbを設定
        self::$breadcrumbList[] = [
            'label' => lang($lang),
        ];

        if (isset($activePrefix)) {
            self::$activePrefix = $activePrefix;
        }
    }

    public static function checkRole($currentName = null)
    {
        if (! isset($currentName)) {
            $currentName = Route::currentRouteName();
        }

        // 必要な権限を取得
        $configuredRoles = config('role');
        $requiredRoles = [];
        foreach ($configuredRoles as $roleName => $routeNameList) {
            foreach ($routeNameList as $routePattern) {
                if (preg_match('/^'.$routePattern.'/', $currentName)) {
                    $requiredRoles[] = $roleName;
                    break;
                }
            }
        }

        // 自分の権限を取得
        $userData = Auth::user();
        $myRoles = empty($userData['role']) ? [] : explode("\t", $userData['role']);

        foreach ($myRoles as $myRole) {
            if (in_array($myRole, $requiredRoles)) {
                return true;
            }
        }

        return false;
    }

    private static function resolveLabel(array $menuConfig)
    {
        if (! isset($menuConfig['label']) && isset($menuConfig['lang'])) {
            $menuConfig['label'] = lang($menuConfig['lang']);
        }

        return $menuConfig;
    }

    private static function resolveUrl(array $menuConfig)
    {
        if (! isset($menuConfig['url']) && isset($menuConfig['name'])) {
            $menuConfig['url'] = empty($menuConfig['argv'])
                ? route($menuConfig['name'])
                : route($menuConfig['name'], $menuConfig['argv']);
        }

        return $menuConfig;
    }

    private static function resolveSubmenu(array $menuConfig, int $currentMaxChild, int $maxChild)
    {
        if (! isset($menuConfig['sub'])) {
            $menuConfig['child'] = $currentMaxChild;

            return [$menuConfig, false, $currentMaxChild, true];
        }

        $subMenuResult = self::get($menuConfig['sub'], $maxChild);
        $subMenuList = $subMenuResult[0] ?? [];
        $isSubActive = $subMenuResult[3] ?? false;
        $childDepth = $subMenuResult[4] ?? $currentMaxChild;

        if (empty($subMenuList)) {
            return [$menuConfig, $isSubActive, $currentMaxChild, false];
        }

        $menuConfig['sub'] = $subMenuList;
        $currentMaxChild = max($currentMaxChild, $childDepth);
        $menuConfig['child'] = $currentMaxChild;

        return [$menuConfig, $isSubActive, $currentMaxChild, true];
    }

    private static function canDisplayMenu(array $menuConfig)
    {
        if (empty($menuConfig['role']) && isset($menuConfig['name']) && ! self::checkRole($menuConfig['name'])) {
            return false;
        }

        if (isset($menuConfig['guard']) && ! Auth::guard($menuConfig['guard'])->check()) {
            return false;
        }

        return true;
    }

    private static function resolveActivePrefix()
    {
        return self::$activePrefix ?? prefix();
    }

    private static function resolveRoutePrefix(array $menuConfig)
    {
        $configNameList = explode('.', $menuConfig['name']);
        array_pop($configNameList);

        return implode('.', $configNameList);
    }

    private static function isMatchingActivePrefix(array $menuConfig)
    {
        // メニューはメソッド以外一致
        return self::resolveRoutePrefix($menuConfig) === self::resolveActivePrefix();
    }
}
