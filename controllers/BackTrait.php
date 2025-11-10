<?php

namespace Blocs\Controllers;

trait BackTrait
{
    protected function backIndex($category = null, $message = null, ...$msgArgList)
    {
        $redirect = redirect()->route(prefix().'.index');
        $this->cleanupBackProperties();

        // メッセージがない場合はそのままリダイレクト
        if (! $category) {
            return $redirect;
        }

        // 言語ファイルからメッセージを取得して引数を置換
        $processedMessage = $this->buildBackMessage($category, $message, $msgArgList);

        $this->outputBackRedirectLog($processedMessage, '一覧画面に戻る', '.index');

        return $redirect->with([
            'category' => $category,
            'message' => $processedMessage,
        ]);
    }

    protected function backCreate($category = null, $message = null, $noticeForm = null, ...$msgArgList)
    {
        $redirect = redirect()->route(prefix().'.create', $this->val)->withInput();
        $this->cleanupBackProperties();

        $this->outputBackRedirectLog($message, '新規作成画面に戻る', '.create');

        return $this->buildBackFormRedirect($redirect, $category, $message, $noticeForm, $msgArgList);
    }

    protected function backEdit($category = null, $message = null, $noticeForm = null, ...$msgArgList)
    {
        $redirect = redirect()->route(prefix().'.edit', $this->val)->withInput();
        $this->cleanupBackProperties();

        $this->outputBackRedirectLog($message, '編集画面に戻る', '.edit');

        return $this->buildBackFormRedirect($redirect, $category, $message, $noticeForm, $msgArgList);
    }

    private function buildBackFormRedirect($redirect, $category, $message, $noticeForm, $msgArgList)
    {
        // カテゴリもフォームエラーも指定されていない場合はそのままリダイレクト
        if (! $category && ! $noticeForm) {
            return $redirect;
        }

        // 言語ファイルからメッセージを取得して引数を置換
        $processedMessage = $this->buildBackMessage($category, $message, $msgArgList);

        // カテゴリが指定されている場合はセッションにメッセージを保存
        if ($category) {
            return $redirect->with([
                'category' => $category,
                'message' => $processedMessage,
            ]);
        }

        // フォームエラーとして返す
        return $redirect->withErrors([
            $noticeForm => $processedMessage,
        ]);
    }

    private function buildBackMessage($category, $message, $msgArgList)
    {
        // 言語ファイル用のコードを生成
        $code = $this->buildBackMessageCode($category, $message, $msgArgList);

        // 言語ファイルからメッセージを取得
        $translatedMessage = $this->fetchBackTranslation($code);

        // 言語ファイルにメッセージがあれば、引数を置換して使用
        if ($translatedMessage !== false) {
            return $this->replaceBackMessagePlaceholders($translatedMessage, $msgArgList);
        }

        // 言語ファイルにない場合は元のメッセージを返す
        return $message;
    }

    private function fetchBackTranslation($code)
    {
        $translatedMessage = lang($code);

        // 翻訳が見つからない場合はコードがそのまま返されるためfalseを返す
        if ($translatedMessage === $code) {
            return false;
        }

        return $translatedMessage;
    }

    private function buildBackMessageCode($category, $message, $msgArgList)
    {
        // 引数をプレースホルダー形式に変換
        $placeholders = [];
        foreach ($msgArgList as $num => $value) {
            $placeholders[] = '{{'.$num.'}}';
        }

        // カテゴリがある場合は「カテゴリ:メッセージ:プレースホルダー」形式、ない場合は「メッセージ:プレースホルダー」形式
        if ($category) {
            $codeParts = array_merge([$category, $message], $placeholders);
        } else {
            $codeParts = array_merge([$message], $placeholders);
        }

        return implode(':', $codeParts);
    }

    private function replaceBackMessagePlaceholders($message, $msgArgList)
    {
        // メッセージ内のプレースホルダーを実際の値に置換
        foreach ($msgArgList as $num => $value) {
            $message = str_replace('{{'.$num.'}}', $value, $message);
        }

        return $message;
    }

    private function cleanupBackProperties()
    {
        // リダイレクト前にプロパティをクリーンアップ
        unset($this->val, $this->request, $this->tableData);
    }

    private function outputBackRedirectLog($message, $destination, $route)
    {
        // ドキュメント用にメッセージとリダイレクト先をログ出力
        docs("遷移後の画面に表示するメッセージを準備する\n- メッセージ: ".$message);
        docs(null, $destination, ['FORWARD' => '!'.prefix().$route]);
    }
}
