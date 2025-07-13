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

        // 検索条件をclear
        if (request()->has('clear')) {
            session()->forget($sessionKey);

            return;
        }

        // viewPrefixが変わるとクリア
        if (($lastSessionKey = session('viewPrefix').'.'.$keyItem) !== $sessionKey) {
            session()->forget($sessionKey);
        }

        // POST
        if (request()->has($keyItem)) {
            $this->saveItem($keyItem, request()->$keyItem, $sessionKey);
            docs(['POST' => $keyItem], 'POSTに<'.$keyItem.'>があれば、セッションに保存', ['セッション' => $keyItem]);

            return;
        }

        // GET
        if (request()->query($keyItem)) {
            $this->saveItem($keyItem, request()->query($keyItem), $sessionKey);

            return;
        }
        docs(['GET' => $keyItem], 'GETに<'.$keyItem.'>があれば、セッションに保存', ['セッション' => $keyItem]);

        if (session()->has($sessionKey)) {
            // sessionがあれば読み込む
            $this->val[$keyItem] = session($sessionKey);
        }
        docs(['セッション' => $keyItem], 'セッションに<'.$keyItem.'>があれば、読み込み');
    }

    private function saveItem($keyItem, $keyValue, $sessionKey)
    {
        if (is_string($keyValue) && strlen($keyValue)) {
            // sessionに保存
            session([$sessionKey => $keyValue]);
            $this->val[$keyItem] = $keyValue;
        } elseif (is_array($keyValue) && count($keyValue)) {
            // 値をマージ
            if (session()->has($sessionKey)) {
                $keyValue = array_merge(session($sessionKey), $keyValue);
            }
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
        docs(['GET' => 'id', 'データベース' => $this->loopItem], '# 現データの取得');
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
        docs(['設定ファイル' => 'config/menu.php'], 'メニュー表示の設定');

        // keepItemで使用
        isset($this->viewPrefix) && session(['viewPrefix' => $this->viewPrefix]);
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

    protected function getLabel($template)
    {
        // 設定ファイルを読み込み
        $path = \Blocs\Common::getPath($template);
        $config = \Blocs\Common::readConfig($path);

        $labels = [];
        if (!isset($config['label'][$path])) {
            return $labels;
        }

        foreach ($config['label'][$path] as $formName => $label) {
            if (false === strpos($label, 'data-')) {
                $labels[$formName] = $label;
            } else {
                isset($blocsCompiler) || $blocsCompiler = new \Blocs\Compiler\BlocsCompiler();
                $labels[$formName] = $blocsCompiler->render($label);
            }
        }

        return $labels;
    }

    protected function getValidate($rules, $messages, $labels)
    {
        $validates = [];
        foreach ($rules as $formName => $formValidates) {
            foreach ($formValidates as $formValidate) {
                if (!is_string($formValidate)) {
                    $formValidate = explode('\\', get_class($formValidate));
                    $formValidate = array_pop($formValidate);
                }

                list($messageKey) = explode(':', $formValidate, 2);
                $messageKey = $formName.'.'.$messageKey;
                $validates[] = [
                    'name' => $labels[$formName] ?? $formName,
                    'validate' => $formValidate,
                    'message' => isset($messages[$messageKey]) ? $messages[$messageKey] : '',
                ];
            }
        }

        return $validates;
    }
}
