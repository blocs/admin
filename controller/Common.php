<?php

namespace Blocs\Controllers;

trait Common
{
    protected function addOption($formName, $optionList)
    {
        \Blocs\Option::add($formName, $optionList);
    }

    protected function keepItem($keyItem)
    {
        if (isset($this->val[$keyItem])) {
            return;
        }

        $sessionKey = $this->viewPrefix.'.search.'.$keyItem;

        if (isset($this->request)) {
            $requestItemList = array_keys($this->request->all());
            if (in_array($keyItem, $requestItemList)) {
                if (isset($this->request->$keyItem) && strlen($this->request->$keyItem)) {
                    // sessionに保存
                    session([$sessionKey => $this->request->$keyItem]);
                } else {
                    // sessionを削除
                    session()->forget($sessionKey);
                }

                return;
            }
        }

        if (session()->has($sessionKey)) {
            // sessionがあれば読み込む
            $this->val[$keyItem] = session($sessionKey);
        }
    }

    public static function getRoutePrefix()
    {
        $currentName = \Route::currentRouteName();

        if (empty($currentName)) {
            return $currentName;
        }

        $currentNameList = explode('.', $currentName);
        array_pop($currentNameList);
        $currentPrefix = implode('.', $currentNameList);

        return $currentPrefix;
    }

    // テーブルのデータと入力値をマージ
    protected static function mergeTable($table, $request)
    {
        if (!is_array($table) || !is_array($request)) {
            return $table;
        }

        foreach ($request as $sKey => $mValue) {
            if (isset($table[$sKey]) && is_array($mValue) && is_array($table[$sKey])) {
                $table[$sKey] = self::mergeTable($table[$sKey], $mValue);
            } else {
                $table[$sKey] = $mValue;
            }
        }

        return $table;
    }

    protected function setupMenu()
    {
        list($menu, $headline, $breadcrumb) = \Blocs\Menu::get();
        $this->val['menu'] = $menu;
        $this->val['headline'] = $headline;
        $this->val['breadcrumb'] = $breadcrumb;
    }
}
