<?php

namespace Blocs\Controllers;

use Illuminate\Http\Request;

trait SelectTrait
{
    use ConfirmTrait;

    protected $selectedIdList = [];

    protected $deletedNum = 0;

    public function confirmSelect(Request $request)
    {
        $this->request = $request;
        $rawInput = $request->all();

        // 選択データのバリデーションを実行（エラーがあればリダイレクト）
        if ($redirect = $this->validateSelect()) {
            return $redirect;
        }

        // 確認画面用のデータをセッションに保存し、表示データを準備
        $this->saveConfirmToSession('select', null, $rawInput);
        $this->prepareConfirmSelect();

        docs('# 画面表示');

        return $this->outputConfirmSelect();
    }

    protected function validateSelect()
    {
        // リクエストから選択されたIDリストを構築
        $this->extractSelectIdList();

        // 選択されたIDが1つもない場合はエラー
        if (empty($this->selectedIdList)) {
            return $this->backIndex('error', 'data_not_selected');
        }
        docs(null, "データが選ばれていないときは、注意メッセージを付けて一覧画面に戻る\n・".lang('error:data_not_selected'), ['FORWARD' => '!'.$this->viewPrefix.'.index']);
    }

    protected function prepareConfirmSelect() {}

    protected function outputConfirmSelect()
    {
        return $this->outputView('confirmSelect', '画面テンプレートを読み込み、表示用のHTMLを生成する');
    }

    public function select(Request $request)
    {
        $this->request = $request;

        // 確認画面からの遷移ならセッションのデータを復元したうえで、バリデーションを実行
        $isConfirmed = $this->hasConfirmInSession('select');
        $isConfirmed || docs(['POST' => '入力値'], '# データの検証');
        if ($redirect = $this->validateBeforeExecute('select', null, fn () => $this->validateSelect(), 'index')) {
            return $redirect;
        }

        // 確認画面からの遷移では、検証を上書きした継承先でも選択IDリストが構築されるようにする
        if ($isConfirmed && empty($this->selectedIdList)) {
            $this->extractSelectIdList();
        }

        // データ一括処理を実行
        docs(['POST' => '入力値'], '# データの一括処理');
        $this->prepareSelect();
        $this->executeSelect();
        $this->logSelect();

        // 処理完了後の画面遷移
        docs('# 画面遷移');

        return $this->outputSelect();
    }

    protected function prepareSelect() {}

    protected function executeSelect()
    {
        // 選択されたIDがない場合は処理をスキップ
        if (empty($this->selectedIdList)) {
            return;
        }

        // データベースから選択されたIDのデータを一括削除
        $this->deletedNum = $this->deleteRecords($this->selectedIdList);
        docs(null, '選ばれた<id>を使ってデータをまとめて削除する', ['データベース' => $this->loopItem]);

        // ログ用のデータを準備（削除したIDリスト）
        $this->setLogData([], $this->selectedIdList);
    }

    protected function outputSelect()
    {
        // 削除完了メッセージと共に一覧画面へ戻る
        return $this->backIndex('success', 'data_deleted', $this->deletedNum);
    }

    /**
     * リクエストのループアイテムから選択されたIDを抽出する。想定外の形の入力は無視する。
     */
    private function extractSelectIdList()
    {
        $rows = $this->request->{$this->loopItem};
        if (! is_array($rows)) {
            return;
        }

        foreach ($rows as $row) {
            $selectedId = is_array($row) && is_array($row['selectedRows'] ?? null) ? ($row['selectedRows'][0] ?? null) : null;

            if (is_int($selectedId) || (is_string($selectedId) && $selectedId !== '')) {
                $this->selectedIdList[] = $selectedId;
            }
        }
    }
}
