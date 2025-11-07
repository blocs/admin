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
        // 既に値が設定されている場合は処理をスキップ
        if (isset($this->val[$keyItem])) {
            return;
        }

        $sessionKey = $this->viewPrefix.'_'.$keyItem;

        // クリアリクエストがあればセッションを削除
        if ($this->shouldKeepClearSession()) {
            session()->forget($sessionKey);

            return;
        }

        // viewPrefixが変更された場合はセッションをクリア
        if ($this->hasKeepViewPrefixChanged()) {
            session()->forget($sessionKey);
        }

        // POSTリクエストから値を取得して保存
        if ($this->handleKeepPostRequest($keyItem, $sessionKey)) {
            return;
        }

        // GETリクエストから値を取得して保存
        if ($this->handleKeepGetRequest($keyItem, $sessionKey)) {
            return;
        }

        // セッションから値を復元
        $this->restoreKeepItemFromSession($keyItem, $sessionKey);
    }

    private function shouldKeepClearSession(): bool
    {
        // クリアパラメータが存在するかチェック
        return request()->has('clear');
    }

    private function hasKeepViewPrefixChanged(): bool
    {
        // セッションに保存されているviewPrefixと現在のviewPrefixを比較
        return session('viewPrefix') !== $this->viewPrefix;
    }

    private function handleKeepPostRequest(string $keyItem, string $sessionKey): bool
    {
        // POSTリクエストに指定されたキーが存在する場合、セッションに保存
        if (request()->has($keyItem)) {
            $this->saveKeepItemToSession($keyItem, request()->$keyItem, $sessionKey);
            docs(['POST' => $keyItem], 'POSTに<'.$keyItem.'>があれば、セッションに保存', ['セッション' => $keyItem]);

            return true;
        }

        return false;
    }

    private function handleKeepGetRequest(string $keyItem, string $sessionKey): bool
    {
        // GETリクエストに指定されたキーが存在する場合、セッションに保存
        if (request()->query($keyItem)) {
            $this->saveKeepItemToSession($keyItem, request()->query($keyItem), $sessionKey);
            docs(['GET' => $keyItem], 'GETに<'.$keyItem.'>があれば、セッションに保存', ['セッション' => $keyItem]);

            return true;
        }

        return false;
    }

    private function restoreKeepItemFromSession(string $keyItem, string $sessionKey): void
    {
        // セッションに値が保存されている場合、それを読み込む
        if (session()->has($sessionKey)) {
            $this->val[$keyItem] = session($sessionKey);
        }
        docs(['セッション' => $keyItem], 'セッションに<'.$keyItem.'>があれば、読み込み');
    }

    private function saveKeepItemToSession(string $keyItem, $keyValue, string $sessionKey): void
    {
        if ($this->isKeepItemValidString($keyValue)) {
            // 文字列の場合、セッションに保存
            $this->storeKeepItemToSession($keyItem, $keyValue, $sessionKey);
        } elseif ($this->isKeepItemValidArray($keyValue)) {
            // 配列の場合、既存の値とマージしてセッションに保存
            $mergedValue = $this->mergeKeepItemWithSession($keyValue, $sessionKey);
            $this->storeKeepItemToSession($keyItem, $mergedValue, $sessionKey);
        } else {
            // 無効な値の場合、セッションを削除
            session()->forget($sessionKey);
        }
    }

    private function isKeepItemValidString($value): bool
    {
        // 空でない文字列かどうかをチェック
        return is_string($value) && strlen($value);
    }

    private function isKeepItemValidArray($value): bool
    {
        // 空でない配列かどうかをチェック
        return is_array($value) && count($value);
    }

    private function mergeKeepItemWithSession(array $keyValue, string $sessionKey): array
    {
        // セッションに既存の値がある場合、新しい値とマージ
        if (session()->has($sessionKey)) {
            $existingValue = session($sessionKey);

            return array_merge(is_array($existingValue) ? $existingValue : [], $keyValue);
        }

        return $keyValue;
    }

    private function storeKeepItemToSession(string $keyItem, $keyValue, string $sessionKey): void
    {
        // セッションと$this->valの両方に値を保存
        session([$sessionKey => $keyValue]);
        $this->val[$keyItem] = $keyValue;
    }

    protected function getCurrent($id)
    {
        docs(['GET' => 'id', 'データベース' => $this->loopItem], '# 現データの取得');
        $this->tableData = $this->mainTable::findOrFail($id);
    }

    // テーブルのデータと入力値を再帰的にマージ
    protected static function mergeTable($table, $request)
    {
        // 両方が配列でない場合はそのまま返す
        if (! is_array($table) || ! is_array($request)) {
            return $table;
        }

        // リクエストの各要素をテーブルデータにマージ
        foreach ($request as $sKey => $mValue) {
            if (isset($table[$sKey]) && is_array($mValue) && is_array($table[$sKey])) {
                // 両方が配列の場合は再帰的にマージ
                $table[$sKey] = self::mergeTable($table[$sKey], $mValue);
            } else {
                // それ以外の場合は値を上書き
                $table[$sKey] = $mValue;
            }
        }

        return $table;
    }

    protected function setupMenu()
    {
        // メニュー設定を取得してビューに渡す
        [$menu, $headline, $breadcrumb] = \Blocs\Menu::get();
        $this->val['menu'] = $menu;
        $this->val['headline'] = $headline;
        $this->val['breadcrumb'] = $breadcrumb;
        docs(['設定ファイル' => 'config/menu.php'], 'メニュー表示の設定');

        // keepItemメソッドで使用するviewPrefixをセッションに保存
        isset($this->viewPrefix) && session(['viewPrefix' => $this->viewPrefix]);
    }

    protected function getLabel($template)
    {
        // 設定ファイルからラベル情報を読み込む
        $path = \Blocs\Common::getPath($template);
        $config = \Blocs\Common::readConfig($path);

        $labels = [];
        if (! isset($config['label'][$path])) {
            return $labels;
        }

        // 各フォーム項目のラベルを処理
        foreach ($config['label'][$path] as $formName => $label) {
            $labels[$formName] = $this->processLabel($label);
        }

        return $labels;
    }

    protected function processLabel($label)
    {
        // data-属性を含まない場合はそのまま返す
        if (strpos($label, 'data-') === false) {
            return $label;
        }

        // data-属性を含む場合はBlocsCompilerでレンダリング
        static $blocsCompiler;
        $blocsCompiler = $blocsCompiler ?? new \Blocs\Compiler\BlocsCompiler;

        return $blocsCompiler->render($label);
    }

    protected function getValidate($rules, $messages, $labels)
    {
        $validates = [];

        // 各フォーム項目のバリデーションルールを処理
        foreach ($rules as $formName => $formValidates) {
            foreach ($formValidates as $formValidate) {
                $validates[] = $this->buildValidateEntry($formName, $formValidate, $messages, $labels);
            }
        }

        return $validates;
    }

    private function buildValidateEntry($formName, $formValidate, $messages, $labels): array
    {
        // バリデーションルールの文字列表現を取得
        $validateString = $this->extractValidateRuleString($formValidate);

        // メッセージキーを生成
        $messageKey = $this->buildValidateMessageKey($formName, $validateString);

        // バリデーション情報を配列で返す
        return [
            'name' => $labels[$formName] ?? $formName,
            'validate' => $validateString,
            'message' => $messages[$messageKey] ?? '',
        ];
    }

    private function extractValidateRuleString($formValidate): string
    {
        // 文字列の場合はそのまま返す
        if (is_string($formValidate)) {
            return $formValidate;
        }

        // オブジェクトの場合はクラス名の末尾部分を取得
        $formValidate = explode('\\', get_class($formValidate));

        return array_pop($formValidate);
    }

    private function buildValidateMessageKey($formName, $validateString): string
    {
        // バリデーションルールからメッセージキーを生成（コロン以前の部分を使用）
        [$messageKey] = explode(':', $validateString, 2);

        return $formName.'.'.$messageKey;
    }
}
