<?php

namespace Blocs\Controllers;

use Illuminate\Http\Request;

trait DestroyTrait
{
    use ConfirmTrait;

    protected $deletedNum = 0;

    public function confirmDestroy($id, Request $request)
    {
        $this->loadCurrent($id, $request);
        $rawInput = $request->all();

        // 入力データのバリデーションを実行（エラーがあればリダイレクト）
        if ($redirect = $this->validateDestroy()) {
            return $redirect;
        }

        // 確認画面用のデータをセッションに保存し、表示データを準備
        $this->saveConfirmToSession('destroy', $id, $rawInput);
        $this->prepareConfirmDestroy();

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
        return $this->outputView('confirmDestroy', '確認画面用のテンプレートを読み込み、HTMLを生成する');
    }

    public function destroy($id, Request $request)
    {
        $this->loadCurrent($id, $request);

        // 確認画面からの遷移ならセッションのデータを復元したうえで、バリデーションを実行
        if ($redirect = $this->validateBeforeExecute('destroy', $id, fn () => $this->validateDestroy(), 'index')) {
            return $redirect;
        }

        // データ削除処理を実行
        docs(['GET' => 'id'], '# 選択されたデータを削除する');
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
        $this->deletedNum = $this->deleteRecords($this->val['id']);
        docs(null, '指定された<id>のデータを一件だけ削除します', ['データベース' => $this->loopItem]);

        // ログ用のデータを設定（削除したID）
        $this->setLogData([], $this->val['id']);
    }

    protected function outputDestroy()
    {
        // 削除完了メッセージと共に一覧画面へ戻る
        return $this->backIndex('success', 'data_deleted', $this->deletedNum);
    }
}
