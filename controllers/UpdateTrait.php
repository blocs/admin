<?php

namespace Blocs\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

trait UpdateTrait
{
    use ConfirmTrait;

    public function edit($id)
    {
        $this->loadCurrent($id);

        // 入力エラーがない場合、テーブルデータを読み込む
        if (empty(old())) {
            $this->val = array_merge($this->tableData->toArray(), $this->val);
        }

        $this->prepareEdit();

        // 確認画面から戻ってきた場合、セッションから入力値を復元
        if ($this->hasConfirmInSession('update', $id)) {
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
        return $this->outputView('edit', '編集画面のテンプレートを読み込み、表示用のHTMLを生成する');
    }

    public function show($id)
    {
        $this->loadCurrent($id);
        $this->val = array_merge($this->tableData->toArray(), $this->val);

        $this->prepareShow();

        docs('# 画面表示');

        return $this->outputShow();
    }

    protected function prepareShow() {}

    protected function outputShow()
    {
        return $this->outputView('show', '詳細画面のテンプレートを読み込み、表示用のHTMLを生成する');
    }

    public function confirmUpdate($id, Request $request)
    {
        $this->loadCurrent($id, $request);
        $rawInput = $request->all();

        // 入力データのバリデーションを実行（エラーがあればリダイレクト）
        docs(['POST' => '入力値'], '# データの検証');
        if ($redirect = $this->validateUpdate()) {
            return $redirect;
        }

        // 確認画面用のデータをセッションに保存し、表示データを準備
        $this->saveConfirmToSession('update', $id, $rawInput);
        $this->prepareConfirmUpdate();

        docs('# 画面表示');

        return $this->outputConfirmUpdate();
    }

    protected function validateUpdate()
    {
        if ($this->validateWithTemplate($this->request, $this->viewPrefix.'.edit')) {
            docs(null, 'エラーがあれば、編集画面に戻る', ['FORWARD' => '!'.$this->viewPrefix.'.edit']);
        }
    }

    protected function prepareConfirmUpdate()
    {
        $this->val = array_merge($this->prepareRequest(), $this->val);
    }

    protected function outputConfirmUpdate()
    {
        return $this->outputView('confirmUpdate', '確認画面のテンプレートを読み込み、表示用のHTMLを生成する');
    }

    public function update($id, Request $request)
    {
        $this->loadCurrent($id, $request);

        // 確認画面からの遷移ならセッションのデータを復元したうえで、バリデーションを実行
        $this->hasConfirmInSession('update', $id) || docs(['POST' => '入力値'], '# データの検証');
        if ($redirect = $this->validateBeforeExecute('update', $id, fn () => $this->validateUpdate(), 'edit', ['id' => $id])) {
            return $redirect;
        }

        // データの同時更新による衝突をチェック
        if ($redirect = $this->checkConflict()) {
            return $redirect;
        }

        // データ更新処理を実行
        docs(['GET' => 'id', 'POST' => '入力値'], '# データの更新');
        $this->executeUpdate($this->prepareUpdate());
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

        // 最終更新日時をチェックして衝突を検出
        docs('ほかの人が同じデータを直していないかを確かめる');
        if ($this->request->updated_at !== $this->tableData->toArray()['updated_at']) {
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
        DB::transaction(fn () => $this->tableData->fill($requestData)->save(), 10);
        docs(null, '指定した<id>のデータを書きかえる処理を行う', ['データベース' => $this->loopItem]);

        $this->setLogData($requestData, $this->val['id']);
    }

    protected function outputUpdate()
    {
        // リクエストに通知項目が含まれている場合はそれを、なければテーブルデータの値を使用
        $noticeItem = $this->request->has($this->noticeItem)
            ? $this->request->{$this->noticeItem}
            : $this->tableData->{$this->noticeItem};

        // 更新完了メッセージと共に一覧画面へ戻る
        return $this->backIndex('success', 'data_updated', $noticeItem);
    }
}
