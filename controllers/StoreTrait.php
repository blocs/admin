<?php

namespace Blocs\Controllers;

use Illuminate\Http\Request;

trait StoreTrait
{
    use ConfirmTrait;

    public function create()
    {
        $this->prepareCreate();

        // 確認画面から戻ってきた場合、セッションから入力値を復元
        if ($this->hasConfirmInSession('store')) {
            $this->val = array_merge($this->val, session($this->viewPrefix.'.confirm'));
        }

        docs('# 画面表示');

        return $this->outputCreate();
    }

    protected function prepareCreate() {}

    protected function outputCreate()
    {
        return $this->outputView('create', '登録画面のテンプレートを読み込み、表示用のHTMLを生成する');
    }

    public function confirmStore(Request $request)
    {
        $this->request = $request;
        $rawInput = $request->all();

        // 入力データのバリデーションを実行（エラーがあればリダイレクト）
        if ($redirect = $this->validateStore()) {
            return $redirect;
        }

        // 確認画面用のデータをセッションに保存し、表示データを準備
        $this->saveConfirmToSession('store', null, $rawInput);
        $this->prepareConfirmStore();

        docs('# 画面表示');

        return $this->outputConfirmStore();
    }

    protected function validateStore()
    {
        if ($this->validateWithTemplate($this->request, $this->viewPrefix.'.create')) {
            docs(null, 'エラーが出たときは新規作成画面に戻り、もう一度入力してもらいます', ['FORWARD' => '!'.$this->viewPrefix.'.create']);
        }
    }

    protected function prepareConfirmStore()
    {
        $this->val = array_merge($this->prepareRequest(), $this->val);
    }

    protected function outputConfirmStore()
    {
        return $this->outputView('confirmStore', '確認画面のテンプレートを読み込み、確認用のHTMLを生成する');
    }

    public function store(Request $request)
    {
        $this->request = $request;

        // 確認画面からの遷移ならセッションのデータを復元したうえで、バリデーションを実行
        $this->hasConfirmInSession('store') || docs(['POST' => '入力値'], '# データの検証');
        if ($redirect = $this->validateBeforeExecute('store', null, fn () => $this->validateStore(), 'create')) {
            return $redirect;
        }

        // データ登録処理を実行
        docs(['POST' => '入力値'], '# データの追加');
        $this->executeStore($this->prepareStore());
        $this->logStore();

        // 登録完了後の画面遷移
        docs('# 画面遷移');

        return $this->outputStore();
    }

    protected function prepareStore()
    {
        return $this->prepareRequest();
    }

    protected function executeStore($requestData = [])
    {
        // 空データの場合は処理をスキップ
        if (empty($requestData)) {
            return;
        }

        $newRecord = $this->createRecord($requestData);
        if ($newRecord === null) {
            return;
        }

        // 作成したレコードのIDを設定
        $this->val['id'] = $newRecord->id;
        docs(null, 'データを追加する', ['データベース' => $this->loopItem]);

        $this->setLogData($requestData, $newRecord->id);
    }

    protected function outputStore()
    {
        // 登録完了メッセージと共に一覧画面へ戻る
        return $this->backIndex('success', 'data_registered', $this->request->{$this->noticeItem});
    }
}
