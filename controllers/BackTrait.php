<?php

namespace Blocs\Controllers;

use Illuminate\Support\Facades\Route;

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
        $this->outputBackRedirectLog($processedMessage, '一覧画面に戻る', 'index');

        return $redirect->with([
            'category' => $category,
            'message' => $processedMessage,
        ]);
    }

    protected function backCreate($category = null, $message = null, $noticeForm = null, ...$msgArgList)
    {
        return $this->backForm('create', '新規作成画面に戻る', $category, $message, $noticeForm, $msgArgList);
    }

    protected function backEdit($category = null, $message = null, $noticeForm = null, ...$msgArgList)
    {
        return $this->backForm('edit', '編集画面に戻る', $category, $message, $noticeForm, $msgArgList);
    }

    /**
     * 入力値を保ったまま入力画面へ戻す。
     * メッセージは、カテゴリがあれば画面上部の通知として、項目名があればその項目のエラーとして表示する。
     */
    private function backForm(string $screen, string $destination, $category, $message, $noticeForm, array $msgArgList)
    {
        $routeName = prefix().'.'.$screen;
        $redirect = redirect()
            ->route($routeName, $this->buildBackRouteParameters($routeName))
            ->withInput(request()->except($this->getDontFlashInputs()));
        $this->cleanupBackProperties();

        $this->outputBackRedirectLog($message, $destination, $screen);

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

    /**
     * 入力画面へ戻すときにセッションへ残さない項目。
     * パスワード類を平文でセッションに保存しないために除外する。
     *
     * @return array<int, string>
     */
    protected function getDontFlashInputs(): array
    {
        return [
            'current_password',
            'password',
            'password_confirmation',
            'password_new',
            'password_old',
            'repassword',
            'repassword_new',
        ];
    }

    /**
     * 戻り先のURLに必要なルートパラメータだけを取り出す。
     * それ以外の値までクエリ文字列としてURLに出さないようにする。
     *
     * @return array<string, mixed>
     */
    private function buildBackRouteParameters(string $routeName): array
    {
        $route = Route::getRoutes()->getByName($routeName);
        if ($route === null) {
            return [];
        }

        return array_intersect_key($this->val ?? [], array_flip($route->parameterNames()));
    }

    /**
     * 言語ファイルからメッセージを取得し、引数を埋め込む。言語ファイルに無ければ渡された文言をそのまま使う。
     * 引数の値に「:」が含まれても区切りと誤解されないよう、いったん {{0}} などの目印に置き換えて取得する。
     */
    private function buildBackMessage($category, $message, $msgArgList)
    {
        // 「カテゴリ:メッセージ:目印…」（カテゴリが無ければ「メッセージ:目印…」）の形で言語ファイルを引く
        $placeholders = array_map(fn ($num) => '{{'.$num.'}}', array_keys($msgArgList));
        $code = implode(':', array_merge($category ? [$category, $message] : [$message], $placeholders));

        // 翻訳が見つからない場合はコードがそのまま返される
        $translatedMessage = lang($code);
        if ($translatedMessage === $code) {
            return $message;
        }

        return str_replace($placeholders, $msgArgList, $translatedMessage);
    }

    private function cleanupBackProperties()
    {
        // リダイレクト前にプロパティをクリーンアップ
        unset($this->val, $this->request, $this->tableData);
    }

    private function outputBackRedirectLog($message, $destination, $screen)
    {
        // ドキュメント用にメッセージとリダイレクト先をログ出力
        docs("遷移後の画面に表示するメッセージを準備する\n- メッセージ: ".$message);
        docs(null, $destination, ['FORWARD' => '!'.prefix().'.'.$screen]);
    }
}
