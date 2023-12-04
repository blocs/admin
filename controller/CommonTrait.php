<?php

namespace Blocs\Controllers;

use Illuminate\Support\Str;

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

        // POST
        if (isset($this->request) && $this->request->has($keyItem)) {
            $this->saveItem($keyItem, $this->request->$keyItem, $sessionKey);

            return;
        }

        // GET
        if (request()->query($keyItem)) {
            $this->saveItem($keyItem, request()->query($keyItem), $sessionKey);

            return;
        }

        if (session()->has($sessionKey)) {
            // sessionがあれば読み込む
            $this->val[$keyItem] = session($sessionKey);
        }
    }

    private function saveItem($keyItem, $keyValue, $sessionKey)
    {
        if (strlen($keyValue)) {
            // sessionに保存
            session([$sessionKey => $keyValue]);
            $this->val[$keyItem] = $keyValue;
        } else {
            // sessionを削除
            session()->forget($sessionKey);
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

    protected function getAccessor($model)
    {
        $methods = get_class_methods($model);

        $accessor = [];
        foreach ($methods as $method) {
            if (!strncmp($method, 'get', 3) && 'Attribute' === substr($method, -9) && $columnName = substr($method, 3, -9)) {
                $columnName = Str::snake($columnName);
                $accessor[$columnName] = $model->$columnName;
            }
        }

        return $accessor;
    }
}
