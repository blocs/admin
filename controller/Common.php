<?php

namespace Blocs\Controllers;

trait Common
{
    protected function add_option($form_name, $options)
    {
        \Blocs\Option::add($form_name, $options);
    }

    protected function keep_item($key_item)
    {
        if (isset($this->val[$key_item])) {
            return;
        }

        $session_key = $this->viewPrefix.'.search.'.$key_item;

        if (isset($this->request)) {
            $request_items = array_keys($this->request->all());
            if (in_array($key_item, $request_items)) {
                if (isset($this->request->$key_item) && strlen($this->request->$key_item)) {
                    // sessionに保存
                    session([$session_key => $this->request->$key_item]);
                } else {
                    // sessionを削除
                    session()->forget($session_key);
                }

                return;
            }
        }

        if (session()->has($session_key)) {
            // sessionがあれば読み込む
            $this->val[$key_item] = session($session_key);
        }
    }

    public function add_item(&$val_table, $item_name, $table_name, $key, $value)
    {
        $keys = [];
        foreach ($val_table as $num => $buff) {
            empty($buff[$item_name]) || $keys[] = $buff[$item_name];
        }
        $keys = array_merge(array_unique($keys));

        // テーブルより追加データを取得
        if (class_exists($table_name)) {
            // Eloquent
            $table = call_user_func($table_name.'::select', $key, $value);
            $table_datas = $table->whereIn($key, $keys)->get();
        } else {
            // クエリビルダ
            $table_datas = \DB::table($table_name)->select($key, $value)->whereIn($key, $keys)->get();
        }

        $replace_item = [];
        foreach ($table_datas as $table_data) {
            $replace_item[$table_data->$key] = $table_data->$value;
        }

        // 元データに追加
        foreach ($val_table as $num => $buff) {
            $val_table[$num][$value] = empty($replace_item[$buff[$item_name]]) ? '' : $replace_item[$buff[$item_name]];
        }
    }
}
