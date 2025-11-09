<?php

namespace Blocs\Controllers;

use Illuminate\Http\Request;

trait SelectTrait
{
    protected $selectedIdList = [];

    protected $deletedNum = 0;

    public function confirmSelect(Request $request)
    {
        $this->request = $request;

        // 選択データのバリデーションを実行（エラーがあればリダイレクト）
        if ($redirect = $this->validateSelect()) {
            return $redirect;
        }

        // 確認画面用のデータをセッションに保存
        $this->saveSelectConfirmToSession();

        // 確認画面の表示データを準備
        $this->prepareConfirmSelect();

        // 確認画面を表示
        docs('# 画面表示');

        return $this->outputConfirmSelect();
    }

    protected function validateSelect()
    {
        // リクエストにループアイテムが存在しない場合はエラー
        if (empty($this->request->{$this->loopItem})) {
            return $this->backIndex('error', 'data_not_selected');
        }

        // リクエストから選択されたIDリストを構築
        $this->extractSelectIdList();

        // 選択されたIDが1つもない場合はエラー
        if (empty($this->selectedIdList)) {
            return $this->backIndex('error', 'data_not_selected');
        }
        docs(null, "データが選ばれていないときは、注意メッセージを付けて一覧画面に戻る\n・".__('error:data_not_selected'), ['FORWARD' => '!'.$this->viewPrefix.'.index']);
    }

    protected function prepareConfirmSelect() {}

    protected function outputConfirmSelect()
    {
        $this->setupMenu();

        $view = view($this->viewPrefix.'.confirmSelect', $this->val);
        unset($this->val, $this->request, $this->tableData);
        docs('画面テンプレートを読み込み、表示用のHTMLを生成する');

        return $view;
    }

    public function select(Request $request)
    {
        $this->request = $request;

        // 確認画面からの遷移かどうかで処理を分岐
        if ($this->hasSelectConfirmInSession()) {
            // 確認画面からの遷移の場合、セッションのデータを復元
            $this->loadSelectConfirmFromSession();

            // セッションから復元したリクエストデータから選択IDリストを構築
            $this->extractSelectIdList();
        } else {
            // 直接実行の場合、バリデーションを実行
            docs(['POST' => '入力値'], '# データの検証');
            if ($redirect = $this->validateSelect()) {
                return $redirect;
            }
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
        $this->executeSelectDeletion();
        docs(null, '選ばれた<id>を使ってデータをまとめて削除する', ['データベース' => $this->loopItem]);

        // ログ用のデータを準備
        $this->buildSelectLogData();
    }

    protected function outputSelect()
    {
        // 削除完了メッセージと共に一覧画面へ戻る
        return $this->backIndex('success', 'data_deleted', $this->deletedNum);
    }

    private function saveSelectConfirmToSession()
    {
        // 確認画面用にリクエストデータをセッションに保存
        session()->flash($this->viewPrefix.'.confirm', $this->request->all());
    }

    private function hasSelectConfirmInSession()
    {
        // 確認画面のセッションデータが存在するかチェック
        return session()->has($this->viewPrefix.'.confirm');
    }

    private function loadSelectConfirmFromSession()
    {
        // セッションから確認画面のデータを復元してリクエストにマージ
        $this->request->merge(session($this->viewPrefix.'.confirm'));
    }

    private function extractSelectIdList()
    {
        // リクエストのループアイテムから選択されたIDを抽出してリストを構築
        foreach ($this->request->{$this->loopItem} as $table) {
            empty($table['selectedRows']) || $this->selectedIdList[] = $table['selectedRows'][0];
        }
    }

    private function executeSelectDeletion()
    {
        // データ削除を実行（エラーは上位に投げる）
        try {
            $this->deletedNum = $this->mainTable::destroy($this->selectedIdList);
        } catch (\Throwable $e) {
            throw $e;
        }
    }

    private function buildSelectLogData()
    {
        // ログデータを準備（削除したIDリスト）
        $this->logData = new \stdClass;
        $this->logData->id = $this->selectedIdList;
    }
}
