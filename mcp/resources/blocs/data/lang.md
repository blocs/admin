# 言語対応のために言語ごとの表示を切り替える方法
`data-lang` 属性を使うと、Laravelの多言語機能と連携して、アプリケーション内で言語ごとに異なるテキストを表示することができます。Laravelの翻訳ファイルに登録された文字列を、HTML内から簡単に呼び出すことができるため、言語切り替えに対応したテンプレートを効率よく作成できます。この属性は、タグ記法とコメント記法の両方で使用できます。

## 言語ファイルの準備
日本語（resources/lang/ja.json）
```json
"success:data_registered": "「{1}」を登録しました。",
```
英語言語ファイル
 英語（resources/lang/en.json）
```json
"success:data_registered": "{1} has been registered.",
```
プレースホルダー（例：{1}）には、`data-lang` でコロン（:）の後に指定した値が順番に挿入されます。

## サンプルコード
この場合、test が {1} に置き換えられます。
日本語表示：「test」を登録しました。
英語表示：test has been registered.
```html
<div data-lang="success:data_registered:test"></div>
```

この形式でも同様に、`data-lang` 属性が解釈され、翻訳された文字列が表示されます。
```html
<div><!--  data-lang="success:data_registered:test" --></div>
```

# QA
## 言語ごとに `tooltip` での表示を切り替えたい。
Bootstrapの `tooltip` に Laravel の翻訳機能を組み合わせることで、言語ごとに異なるメッセージを表示できます。BLOCSの `lang()` 関数を使えば、`data-lang` と同様に翻訳ファイルから文字列を取得できます。以下のように記述することで、`template:admin_user_invalid_title` に登録された翻訳文字列が、現在の言語設定に応じて `tooltip` に表示されます。
```html
<i class="fa-solid fa-ban" data-bs-toggle="tooltip" :data-bs-original-title=lang("template:admin_user_invalid_title")></i>
```

Bootstrapの `tooltip` は、`data-bs-html="true"` を指定することで、HTMLタグを含む内容をレンダリングできます。以下の場合、ツールチップ内で `i` タグが有効になり、スタイル付きの表示が可能になります。
```html
<i class="fa-solid fa-ban" data-bs-toggle="tooltip" data-bs-html="true" data-bs-original-title="<i>ERROR</i>"></i>
```

## `tooltip` を表示したい。
Bootstrapの `tooltip` 機能を使えば、アイコンやボタンなどにマウスを乗せたときに補足情報を表示できます。Laravel の翻訳機能と組み合わせることで、言語ごとに異なるメッセージを表示することも可能です。以下のように記述することで、ERROR というツールチップが表示されます。
```html
<i class="fa-solid fa-ban" data-bs-toggle="tooltip" data-bs-original-title="ERROR"></i>
```

BLOCSの `lang()` 関数を使えば、翻訳ファイルに登録された文字列を `tooltip` に表示できます。以下の場合、`template:admin_user_invalid_title` に登録された翻訳文字列が、現在の言語設定に応じて表示されます。
```html
<i class="fa-solid fa-ban" data-bs-toggle="tooltip" :data-bs-original-title=lang("template:admin_user_invalid_title")></i>
```
