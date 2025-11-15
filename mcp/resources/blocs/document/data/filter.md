# バリデーション前に入力値を整形する方法
入力された値を、バリデーション処理の前に指定した形式へ変換したい場合は、`data-filter` 属性を使用します。この属性を使うことで、ユーザーが入力した値を事前に整形し、処理しやすい形に変えることができます。なお、`data-convert` は表示専用の変換であり、元の値は変わりませんが、`data-filter` は実際の値そのものを変換するため、用途が異なります。`data-filter` はタグ記法でのみ使用可能で、コメント記法では利用できません。

複数のフィルターを適用したい場合は、|（パイプ）でつなげて記述します。たとえば、`trim|toLowerCase` のように書くことで、前後の空白を除去したあとに小文字へ変換する処理が行われます。

## サンプルコード
この例では、入力された値が自動的に「全角カタカナ」に変換されます。
```html
<input type="text" name="name" data-filter="katakana" />
```

## 利用可能な変換形式一覧
以下の変換形式をご利用いただけます。独自のフィルター関数や標準関数を作成して、`data-filter` に指定することも可能です。入力値の整形を通じて、バリデーションの精度やユーザー体験を向上させることができます。

|data-filterの値|変換内容|
|:-----------|:-----------|
|single|半角文字列に変換する|
|multi|全角文字列に変換する|
|hiragana|ひらがなに変換する|
|katakana|全角カタカナに変換する|
|halfKatakana|半角カタカナに変換する|
|antiHalfKatakana|半角カタカナのみ全角に変換する|
|phone|電話番号の形式に整形する|
|postal|郵便番号の形式に整形する|
|datepicker|日付の形式に整形する|

## 回答例
- **user**
`data-filter` 属性で独自の変換ロジックを追加したい場合、どのような手順で独自フィルター関数を定義し、テンプレートから呼び出せるようにすればよいでしょうか？
- **assistant**
テンプレート内で `data-filter` 属性を使って独自の変換処理を行いたい場合、以下の手順で実装できます。まず、`public` かつ `static` なメソッドを定義します。必要に応じて引数も受け取れます。
```php
public static function unit($str, $parameter1)
{
    return $str.$parameter1;
}
```
- **assistant**
テンプレート内で `data-filter` 属性を使い、クラスとメソッドを指定して呼び出します。コロン `:` で区切って引数も渡せます。
```html
<input type="text" name="name" data-filter="App\Http\Controllers\Admin\UserController::unit:'円'" />
```
---
