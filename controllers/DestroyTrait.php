<?php

namespace Blocs\Controllers;

use Illuminate\Http\Request;

trait DestroyTrait
{
    protected $deletedNum = 0;

    public function confirmDestroy($id, Request $request)
    {
        // 削除対象のデータを取得してリクエストを設定
        $this->initializeDestroyContext($id, $request);

        // 入力データのバリデーションを実行（エラーがあればリダイレクト）
        if ($redirect = $this->validateDestroy()) {
            return $redirect;
        }

        // 確認画面用のデータをセッションに保存
        $this->saveDestroyConfirmToSession();

        // 確認画面の表示データを準備
        $this->prepareConfirmDestroy();

        // 確認画面を表示
        docs('# 画面表示');

        return $this->outputConfirmDestroy();
    }

    protected function validateDestroy() {}

    protected function prepareConfirmDestroy()
    {
        $this->val = array_merge($this->prepareRequest(), $this->val);
    }

    protected function outputConfirmDestroy()
    {
        $this->setupMenu();

        $view = view($this->viewPrefix.'.confirmDestroy', $this->val);
        unset($this->val, $this->request, $this->tableData);
        docs('テンプレートを読み込んで、HTMLを生成');

        return $view;
    }

    public function destroy($id, Request $request)
    {
        // 削除対象のデータを取得してリクエストを設定
        $this->initializeDestroyContext($id, $request);

        // 確認画面からの遷移かどうかで処理を分岐
        if ($this->hasDestroyConfirmInSession()) {
            // 確認画面からの遷移の場合、セッションのデータを復元
            $this->loadDestroyConfirmFromSession();
        } else {
            // 直接実行の場合、バリデーションを実行
            if ($redirect = $this->validateDestroy()) {
                return $redirect;
            }
        }

        // データ削除処理を実行
        docs('# データの削除');
        $this->prepareDestroy();
        $this->executeDestroy();
        $this->logDestroy();

        // 削除完了後の画面遷移
        docs('# 画面遷移');

        return $this->outputDestroy();
    }

    protected function prepareDestroy() {}

    protected function executeDestroy()
    {
        // データベースから指定されたIDのデータを削除
        $this->executeDestroyDeletion();

        // ドキュメント出力
        docs(['GET' => 'id'], '<id>を指定してデータを削除', ['データベース' => $this->loopItem]);

        // ログ用のデータを設定
        $this->buildDestroyLogData();
    }

    protected function outputDestroy()
    {
        // 削除完了メッセージと共に一覧画面へ戻る
        return $this->backIndex('success', 'data_deleted', $this->deletedNum);
    }

    private function initializeDestroyContext($id, Request $request)
    {
        // 削除対象のデータをデータベースから取得
        $this->getCurrent($id);

        // IDとリクエストを設定
        $this->val['id'] = $id;
        $this->request = $request;
    }

    private function saveDestroyConfirmToSession()
    {
        // 確認画面用にリクエストデータをセッションに保存
        session()->flash($this->viewPrefix.'.confirm', $this->request->all());
    }

    private function hasDestroyConfirmInSession()
    {
        // 確認画面のセッションデータが存在するかチェック
        return session()->has($this->viewPrefix.'.confirm');
    }

    private function loadDestroyConfirmFromSession()
    {
        // セッションから確認画面のデータを復元してリクエストにマージ
        $this->request->merge(session($this->viewPrefix.'.confirm'));
    }

    private function executeDestroyDeletion()
    {
        // データ削除を実行（エラーは上位に投げる）
        try {
            $this->deletedNum = $this->mainTable::destroy($this->val['id']);
        } catch (\Throwable $e) {
            throw $e;
        }
    }

    private function buildDestroyLogData()
    {
        // ログデータを準備（削除したID）
        $this->logData = new \stdClass;
        $this->logData->id = $this->val['id'];
    }
}
