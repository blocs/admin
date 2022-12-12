<?php

namespace Blocs\Controllers;

trait Common
{
    protected function add_option($formName, $options)
    {
        \Blocs\Option::add($formName, $options);
    }

    protected function keep_item($keyItem)
    {
        if (isset($this->val[$keyItem])) {
            return;
        }

        $sessionKey = $this->viewPrefix.'.search.'.$keyItem;

        if (isset($this->request)) {
            $requestItems = array_keys($this->request->all());
            if (in_array($keyItem, $requestItems)) {
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

    protected function add_item(&$valTable, $itemName, $tableName, $key, $value)
    {
        $keys = [];
        foreach ($valTable as $num => $buff) {
            empty($buff[$itemName]) || $keys[] = $buff[$itemName];
        }
        $keys = array_merge(array_unique($keys));

        // テーブルより追加データを取得
        if (class_exists($tableName)) {
            // Eloquent
            $table = call_user_func($tableName.'::select', $key, $value);
            $tableDatas = $table->whereIn($key, $keys)->get();
        } else {
            // クエリビルダ
            $tableDatas = \DB::table($tableName)->select($key, $value)->whereIn($key, $keys)->get();
        }

        $replaceItem = [];
        foreach ($tableDatas as $tableData) {
            $replaceItem[$tableData->$key] = $tableData->$value;
        }

        // 元データに追加
        foreach ($valTable as $num => $buff) {
            $valTable[$num][$value] = empty($replaceItem[$buff[$itemName]]) ? '' : $replaceItem[$buff[$itemName]];
        }
    }
}
