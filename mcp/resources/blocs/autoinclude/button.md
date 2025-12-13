# ボタンを表示する方法
Auto Include 機能を利用することで、テンプレート内にさまざまな種類のボタンを簡単に表示できます。この機能により、複雑な記述をせずに、用途に応じたボタン（送信、キャンセル、ダウンロードなど）を素早く設置することが可能です。

## 定義済みのボタンのブロック
以下のようなボタンブロックがあらかじめ定義されています。

|ブロック名|用途|
|:-----------|:-----------|
|button_href|通常のリンクボタン|
|button_back|戻るリンクボタン|
|button_create|新規登録用のリンクボタン|
|button|通常のボタン|
|button_primary|モーダルを開く青色の主ボタン|
|button_info|モーダルを開く水色の情報ボタン|
|button_success|モーダルを開く緑色の成功ボタン|
|button_danger|モーダルを開く赤色の警告ボタン|
|button_warning|モーダルを開く黄色の注意ボタン|

## ボタンのカスタマイズ方法（変数の使用）
`data-include` 属性に **引数（変数）** を追加することで、ボタンのリンク先やデザイン、アイコン、ラベルなどを自由にカスタマイズできます。

|引数|説明|
|:-----------|:-----------|
|$buttonHref|リンクボタンのリンク先 URL|
|$buttonType|通常ボタンのタイプ|
|$buttonClass|ボタンに適用する CSS クラス|
|$buttonIcon|表示するアイコン（Font Awesomeなど）|
|$buttonLabel|ボタンに表示するテキスト|

### サンプルコード
```html
<!--
    data-include="button_href"
    $buttonHref=route("admin.user.create")
    $buttonClass="btn btn-primary"
    $buttonIcon="fa-solid fa-plus"
    $buttonLabel="新規作成"
-->
```
