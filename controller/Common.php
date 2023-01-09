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
}
