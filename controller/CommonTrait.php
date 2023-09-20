<?php

namespace Blocs\Controllers;

trait CommonTrait
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

        $sessionKey = $this->viewPrefix.'.'.$keyItem;

        if (isset($this->request)) {
            if ($this->request->has($keyItem)) {
                if (strlen($this->request->$keyItem)) {
                    // sessionに保存
                    session([$sessionKey => $this->request->$keyItem]);
                    $this->val[$keyItem] = $this->request->$keyItem;
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

    protected function getCurrent($id)
    {
        $this->tableData = $this->mainTable::findOrFail($id);
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

    protected function setAutoinclude($autoincludeDir)
    {
        $GLOBALS[\Route::currentRouteAction()]['BLOCS_AUTOINCLUDE_DIR'] = $autoincludeDir;
    }
}
