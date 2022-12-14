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

    protected function addItem(&$valTable, $itemName, $tableName, $key, $value)
    {
        $keyList = [];
        foreach ($valTable as $num => $buff) {
            empty($buff[$itemName]) || $keyList[] = $buff[$itemName];
        }
        $keyList = array_merge(array_unique($keyList));

        // テーブルより追加データを取得
        if (class_exists($tableName)) {
            // Eloquent
            $table = call_user_func($tableName.'::select', $key, $value);
            $tableDataList = $table->whereIn($key, $keyList)->get();
        } else {
            // クエリビルダ
            $tableDataList = \DB::table($tableName)->select($key, $value)->whereIn($key, $keyList)->get();
        }

        $replaceItem = [];
        foreach ($tableDataList as $tableData) {
            $replaceItem[$tableData->$key] = $tableData->$value;
        }

        // 元データに追加
        foreach ($valTable as $num => $buff) {
            $valTable[$num][$value] = empty($replaceItem[$buff[$itemName]]) ? '' : $replaceItem[$buff[$itemName]];
        }
    }
}
