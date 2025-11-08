<?php

namespace Blocs\Controllers;

use Illuminate\Http\Request;

trait StoreTrait
{
    public function create()
    {
        $this->prepareCreate();

        // 確認画面から戻ってきた場合、セッションから入力値を復元
        if ($this->hasStoreConfirmInSession()) {
            $this->val = array_merge($this->val, session($this->viewPrefix.'.confirm'));
        }

        docs('# 画面表示');

        return $this->outputCreate();
    }

    protected function prepareCreate() {}

    protected function outputCreate()
    {
        $this->setupMenu();

        $view = view($this->viewPrefix.'.create', $this->val);
        unset($this->val, $this->request, $this->tableData);
        docs('登録画面のテンプレートを読み込み、表示用のHTMLを生成する');

        return $view;
    }

    public function confirmStore(Request $request)
    {
        $this->request = $request;

        // 入力データのバリデーションを実行（エラーがあればリダイレクト）
        if ($redirect = $this->validateStore()) {
            return $redirect;
        }

        // 確認画面用のデータをセッションに保存
        $this->saveStoreConfirmToSession();

        // 確認画面の表示データを準備
        $this->prepareConfirmStore();

        // 確認画面を表示
        docs('# 画面表示');

        return $this->outputConfirmStore();
    }

    protected function validateStore()
    {
        // バリデーションルールとメッセージを取得
        [$rules, $messages] = \Blocs\Validate::get($this->viewPrefix.'.create', $this->request);
        if (empty($rules)) {
            return;
        }

        // バリデーションを実行してエラーがあればメッセージをセット
        $labels = $this->getLabel($this->viewPrefix.'.create');
        $this->request->validate($rules, $messages, $labels);
        $validates = $this->getValidate($rules, $messages, $labels);
        docs(null, '入力値を以下の条件で検証して、エラーがあればメッセージをセットする', null, $validates);
        docs(null, 'エラーが出たときは新規作成画面に戻り、もう一度入力してもらいます', ['FORWARD' => '!'.$this->viewPrefix.'.create']);
    }

    protected function prepareConfirmStore()
    {
        $this->val = array_merge($this->prepareRequest(), $this->val);
    }

    protected function outputConfirmStore()
    {
        $this->setupMenu();

        $view = view($this->viewPrefix.'.confirmStore', $this->val);
        unset($this->val, $this->request, $this->tableData);
        docs('確認画面のテンプレートを読み込み、確認用のHTMLを生成する');

        return $view;
    }

    public function store(Request $request)
    {
        $this->request = $request;

        // 確認画面からの遷移かどうかで処理を分岐
        if ($this->hasStoreConfirmInSession()) {
            // 確認画面からの遷移の場合、セッションのデータを復元
            $this->loadStoreConfirmFromSession();
        } else {
            // 直接実行の場合、バリデーションを実行
            docs(['POST' => '入力値'], '# データの検証');
            if ($redirect = $this->validateStore()) {
                return $redirect;
            }
        }

        // データ登録処理を実行
        docs(['POST' => '入力値'], '# データの追加');
        $preparedData = $this->prepareStore();
        $this->executeStore($preparedData);
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

        // トランザクション内で新規データを作成
        $newRecord = $this->insertStoreRecordWithTransaction($requestData);

        if ($newRecord === null) {
            return;
        }

        // 作成したレコードのIDを設定
        $this->val['id'] = $newRecord->id;
        docs(null, 'データを追加する', ['データベース' => $this->loopItem]);

        // ログ用のデータを準備
        $this->buildStoreLogData($requestData, $newRecord->id);
    }

    protected function outputStore()
    {
        // 登録完了メッセージと共に一覧画面へ戻る
        return $this->backIndex('success', 'data_registered', $this->request->{$this->noticeItem});
    }

    private function hasStoreConfirmInSession()
    {
        // 確認画面のセッションデータが存在するかチェック
        return session()->has($this->viewPrefix.'.confirm');
    }

    private function saveStoreConfirmToSession()
    {
        // 確認画面用にリクエストデータをセッションに保存
        session()->flash($this->viewPrefix.'.confirm', $this->request->all());
    }

    private function loadStoreConfirmFromSession()
    {
        // セッションから確認画面のデータを復元してリクエストにマージ
        $this->request->merge(session($this->viewPrefix.'.confirm'));
    }

    private function insertStoreRecordWithTransaction($requestData)
    {
        // データベーストランザクション内でレコードを作成
        $newRecord = null;
        \Illuminate\Support\Facades\DB::transaction(function () use ($requestData, &$newRecord) {
            $newRecord = $this->mainTable::create($requestData);
        }, 10);

        return $newRecord;
    }

    private function buildStoreLogData($requestData, $newId)
    {
        // ログデータを準備（登録内容 + 新規ID）
        $this->logData = (object) $requestData;
        $this->logData->id = $newId;
    }
}
