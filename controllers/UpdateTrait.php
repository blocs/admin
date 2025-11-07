<?php

namespace Blocs\Controllers;

use Illuminate\Http\Request;

trait UpdateTrait
{
    public function edit($id)
    {
        // 編集対象のデータを取得してIDを設定
        $this->initializeUpdateContext($id);

        // 入力エラーがない場合、テーブルデータを読み込む
        if (empty(old())) {
            $this->val = array_merge($this->tableData->toArray(), $this->val);
        }

        // 編集画面用のデータを準備
        $this->prepareEdit();

        // 確認画面から戻ってきた場合、セッションから入力値を復元
        if ($this->hasUpdateConfirmInSession()) {
            $this->val = array_merge($this->val, session($this->viewPrefix.'.confirm'));
        }

        docs('# 画面表示');

        return $this->outputEdit();
    }

    protected function prepareEdit()
    {
        return $this->prepareCreate();
    }

    protected function outputEdit()
    {
        $this->setupMenu();

        $view = view($this->viewPrefix.'.edit', $this->val);
        unset($this->val, $this->request, $this->tableData);
        docs('テンプレートを読み込んで、HTMLを生成');

        return $view;
    }

    public function show($id)
    {
        // 表示対象のデータを取得してIDを設定
        $this->initializeUpdateContext($id);

        // テーブルデータを読み込む
        $this->val = array_merge($this->tableData->toArray(), $this->val);

        // 詳細画面用のデータを準備
        $this->prepareShow();

        docs('# 画面表示');

        return $this->outputShow();
    }

    protected function prepareShow() {}

    protected function outputShow()
    {
        $this->setupMenu();

        $view = view($this->viewPrefix.'.show', $this->val);
        unset($this->val, $this->request, $this->tableData);
        docs('テンプレートを読み込んで、HTMLを生成');

        return $view;
    }

    public function confirmUpdate($id, Request $request)
    {
        // 更新対象のデータを取得してリクエストを設定
        $this->initializeUpdateContext($id, $request);

        // 入力データのバリデーションを実行（エラーがあればリダイレクト）
        if ($redirect = $this->validateUpdate()) {
            return $redirect;
        }

        // 確認画面用のデータをセッションに保存
        $this->saveUpdateConfirmToSession();

        // 確認画面の表示データを準備
        $this->prepareConfirmUpdate();

        // 確認画面を表示
        docs('# 画面表示');

        return $this->outputConfirmUpdate();
    }

    protected function validateUpdate()
    {
        // バリデーションルールとメッセージを取得
        [$rules, $messages] = \Blocs\Validate::get($this->viewPrefix.'.edit', $this->request);
        if (empty($rules)) {
            return;
        }

        // バリデーションを実行してエラーがあればメッセージをセット
        $labels = $this->getLabel($this->viewPrefix.'.edit');
        $this->request->validate($rules, $messages, $labels);
        $validates = $this->getValidate($rules, $messages, $labels);
        docs(['POST' => '入力値'], '入力値を以下の条件で検証して、エラーがあればメッセージをセット', null, $validates);
        docs(null, 'エラーがあれば、編集画面に戻る', ['FORWARD' => '!'.$this->viewPrefix.'.edit']);
    }

    protected function prepareConfirmUpdate()
    {
        $this->val = array_merge($this->prepareRequest(), $this->val);
    }

    protected function outputConfirmUpdate()
    {
        $this->setupMenu();

        $view = view($this->viewPrefix.'.confirmUpdate', $this->val);
        unset($this->val, $this->request, $this->tableData);
        docs('テンプレートを読み込んで、HTMLを生成');

        return $view;
    }

    public function update($id, Request $request)
    {
        // 更新対象のデータを取得してリクエストを設定
        $this->initializeUpdateContext($id, $request);

        // 確認画面からの遷移かどうかで処理を分岐
        if ($this->hasUpdateConfirmInSession()) {
            // 確認画面からの遷移の場合、セッションのデータを復元
            $this->loadUpdateConfirmFromSession();
        } else {
            // 直接実行の場合、バリデーションを実行
            docs('# データの検証');
            if ($redirect = $this->validateUpdate()) {
                return $redirect;
            }
        }

        // データの同時更新による衝突をチェック
        if ($redirect = $this->checkConflict()) {
            return $redirect;
        }

        // データ更新処理を実行
        docs('# データの更新');
        $preparedData = $this->prepareUpdate();
        $this->executeUpdate($preparedData);
        $this->logUpdate();

        // 更新完了後の画面遷移
        docs('# 画面遷移');

        return $this->outputUpdate();
    }

    protected function checkConflict()
    {
        // updated_atが送信されていない場合は衝突チェックをスキップ
        if (empty($this->request->updated_at)) {
            return;
        }

        $tableData = $this->tableData->toArray();

        // 最終更新日時をチェックして衝突を検出
        docs('データの衝突チェック');
        if ($this->request->updated_at !== $tableData['updated_at']) {
            return $this->backEdit('error', 'collision_happened');
        }
    }

    protected function prepareUpdate()
    {
        return $this->prepareStore();
    }

    protected function executeUpdate($requestData = [])
    {
        // 空データの場合は処理をスキップ
        if (empty($requestData)) {
            return;
        }

        // トランザクション内でデータを更新
        $tableData = $this->tableData;
        \Illuminate\Support\Facades\DB::transaction(function () use ($tableData, $requestData) {
            $tableData->fill($requestData)->save();
        }, 10);

        docs(['GET' => 'id', 'POST' => '入力値'], '<id>を指定してデータを更新', ['データベース' => $this->loopItem]);

        // ログ用のデータを準備
        $this->buildUpdateLogData($requestData);
    }

    protected function outputUpdate()
    {
        // 更新通知用の項目を取得
        $noticeItem = $this->extractUpdateNoticeItem();

        // 更新完了メッセージと共に一覧画面へ戻る
        return $this->backIndex('success', 'data_updated', $noticeItem);
    }

    private function initializeUpdateContext($id, ?Request $request = null)
    {
        // 更新対象のデータをデータベースから取得
        $this->getCurrent($id);

        // IDを設定
        $this->val['id'] = $id;

        // リクエストが渡された場合は設定
        if ($request !== null) {
            $this->request = $request;
        }
    }

    private function hasUpdateConfirmInSession()
    {
        // 確認画面のセッションデータが存在するかチェック
        return session()->has($this->viewPrefix.'.confirm');
    }

    private function saveUpdateConfirmToSession()
    {
        // 確認画面用にリクエストデータをセッションに保存
        session()->flash($this->viewPrefix.'.confirm', $this->request->all());
    }

    private function loadUpdateConfirmFromSession()
    {
        // セッションから確認画面のデータを復元してリクエストにマージ
        $this->request->merge(session($this->viewPrefix.'.confirm'));
    }

    private function buildUpdateLogData($requestData)
    {
        // ログデータを準備（更新内容 + ID）
        $this->logData = (object) $requestData;
        $this->logData->id = $this->val['id'];
    }

    private function extractUpdateNoticeItem()
    {
        // リクエストに通知項目が含まれている場合はそれを使用
        if ($this->request->has($this->noticeItem)) {
            return $this->request->{$this->noticeItem};
        }

        // なければテーブルデータから取得
        return $this->tableData->{$this->noticeItem};
    }
}
