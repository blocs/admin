# 自動生成した管理画面の新規作成・編集機能をカスタマイズする方法
自動生成ツールで生成した管理画面には、標準で新規作成・編集機能が組み込まれています。この機能は、テンプレートや関連メソッドを通じて実装されており、プロジェクトの要件に応じてカスタマイズすることが可能です。

## テンプレートの一覧
編集画面は、複数のテンプレートファイルによって構成されており、共通テンプレートは `resources/views/admin/base/entry` に格納されています。このディレクトリ内には、画面の各パーツを構成するテンプレートが用意されており、用途ごとに分かれています。

|テンプレート名|用途|
|:-----------|:-----------|
|header.html|画面上部のヘッダー部分|
|footer.html|画面下部のフッター部分|

|テンプレート名|用途|
|:-----------|:-----------|
|include/entry.html|編集画面で共通して使用されるレイアウトテンプレート|
|include/form.html|入力フォームの部品テンプレート（項目ごとの入力欄など）|
|create.blocs.html|新規作成画面のメインテンプレート|
|edit.blocs.html|編集画面のメインテンプレート|
|confirm.blocs.html|入力内容の確認画面テンプレート|

## ベースコントローラーの編集機能に含まれるメソッド
ベースコントローラーには、新規作成・編集処理に必要な一連のメソッドが定義されています。それぞれの処理は、画面表示・バリデーション・データ登録・ビュー出力などの役割に分かれており、柔軟にカスタマイズ可能です。

|メソッド（ルート名）|メソッド（ルート名）|処理内容|
|:-----------|:-----------|:-----------|
|public|create|新規作成画面を表示|
|protected|prepareCreate|出力のためのデータ処理|
|protected|outputCreate|ビューの出力処理|
|public|confirmStore|入力内容の確認画面を表示|
|protected|validateStore|入力値のバリデーション|
|protected|prepareConfirmStore|出力のためのデータ処理|
|protected|outputConfirmStore|ビューの出力処理|
|public|store|新規登録処理|
|protected|prepareStore|出力のためのデータ処理|
|protected|executeStore|データの新規登録|
|protected|outputStore|ビューの出力処理|

|メソッド（ルート名）|メソッド（ルート名）|処理内容|
|:-----------|:-----------|:-----------|
|public|edit|編集画面を表示|
|protected|prepareEdit|出力のためのデータ処理|
|protected|outputEdit|ビューの出力処理|
|public|confirmUpdate|入力内容の確認画面を表示|
|protected|validateUpdate|入力値のバリデーション|
|protected|prepareConfirmUpdate|出力のためのデータ処理|
|protected|outputConfirmUpdate|ビューの出力処理|
|public|update|編集処理の実行|
|protected|checkConflict|編集の競合（同時編集など）の確認|
|protected|prepareUpdate|出力のためのデータ処理|
|protected|executeUpdate|データの更新処理|
|protected|outputStore|ビューの出力処理|

## ベースコントローラーで使用できる主な変数
ベースコントローラーでは、以下の変数を使ってリクエスト情報やデータの操作が可能です。

|変数名|説明|
|:-----------|:-----------|
|$this->request|現在のリクエスト情報を保持します。フォームの入力値やURLパラメータなどを取得する際に使用します。|
|$this->tableData|編集対象のデータを格納します。データベースから取得したレコードの内容を操作する際に使用します。|

## 編集時に現在のデータを取得する方法
編集画面で対象データの現在の内容を取得したい場合は、コントローラー内で `$this->getCurrent($id)` を使用します。

このメソッドに対象の `$id` を渡すことで、該当するレコードの最新データを取得できます。取得したデータは、フォームの初期値として表示したり、編集前の状態を確認するために利用できます。

## 編集の競合確認を有効にする方法
複数ユーザーによる同時編集などの **競合を検出** したい場合は、テンプレート内で `updated_at` の値を `hidden`フィールドとして送信することで対応できます。
```html
<input type="hidden" name="updated_at" />
```

このように記述することで、編集対象データの最終更新日時（`updated_at`）がフォーム送信時にサーバーへ渡されます。コントローラー側では、この値をもとに、保存前にデータの更新状況をチェックし、競合が発生していないかを確認できます。

## 確認画面の追加方法
管理画面の初期状態では、モーダルによる確認が行われ、専用の確認画面は存在しません。確認画面を新たに追加したい場合は、以下の手順で実装します。

1. 確認画面用テンプレートを作成する  
`resources/views/...` 配下に、確認画面用のテンプレートファイル `confirmStore.blocs.html` を作成します。
このテンプレートには、入力内容の確認表示や「登録」ボタンなどを配置します。

2. 確認画面を表示するルートを定義する  
コントローラーの `confirmStore` メソッドをルーティングに追加します。
```php
Route::get('/confirmStore', [UserController::class, 'confirmStore'])->name('confirmStore');
```

3. 新規作成画面から確認画面へ遷移するボタンを設置  
`confirmStore` に、確認画面へ遷移するための送信ボタンを追加します。
```html
<!-- data-bloc="buttonBottomCenter" -->
    <!-- :formaction=route(prefix().".confirmStore") -->
    <!--
        data-include="button"
        $buttonType="submit"
        $buttonClass="btn btn-primary"
        $buttonIcon="fa-solid fa-arrow-right"
        $buttonLabel="確認"
    -->
<!-- data-endbloc -->
```

このブロックを追加することで、「確認」ボタンが画面下部中央に表示され、クリックすると `confirmStore` ルートへ遷移します。