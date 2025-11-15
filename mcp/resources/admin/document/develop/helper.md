# 拡張ヘルパー関数の使い方
このパッケージには、標準機能を補うために独自に拡張されたヘルパー関数が含まれています。これらの関数を利用することで、処理の簡略化やコードの再利用性が向上し、開発効率を高めることができます。

## lang($str)
Laravelの翻訳ファイルから、指定したキーに対応する文字列（翻訳文）を取得します。テンプレートやコントローラー内で、言語対応のメッセージを表示したいときに使います。
```html
<#-- $uploadMessage=lang("template:admin_profile_upload_message") -->
```

## val($str, $formName, $template)
指定したテンプレートとフォーム名に基づいて、メニューのラベルを取得します。`$str` に渡された値を、対応するラベルに変換します。コントローラーでラベル表示をしたいときに便利です。
```php
$category = val($category, 'category', 'admin.company.index');
```

## prefix()
現在のルート名から、メソッド名の直前までの部分（プレフィックス）を取得します。ルートを動的に生成したいときに使います。
```html
<!-- $buttonHref=route(prefix().".index") -->
```

## path()
`route()` 関数とは異なり、絶対URLではなく相対パスのURLを取得します。テンプレートで相対リンクを作成したい場合に使用します。
```html
<!-- $buttonHref=path(prefix().".index") -->
```

## getOption($formName, $template)
指定したテンプレートとフォーム名に対応する、選択肢（ラベル）の一覧を取得します。フォームのセレクトボックスなどに使う選択肢を取得したいときに使用します。
```php
$options = getOption('category', 'admin.company.index');
```

## addOption($formName, $optionList)
既存の選択肢に、新しい項目を追加します。コントローラー側で、テンプレートに渡す選択肢を動的に増やしたいときに使います。
```php
addOption("type", ["foreign" => "外国のお客様"]);
```

## setOption($formName, $template)
現在のテンプレートとは別のテンプレートから、選択肢（ラベル）を読み込みます。共通の選択肢を他のテンプレートから再利用したいときに便利です。
```php
setOption('category', 'admin.company.index');
```
