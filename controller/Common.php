<?php

namespace Blocs\Controllers;

trait Common
{
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
}
