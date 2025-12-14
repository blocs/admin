# 変数の表示形式を整形する方法
テンプレートで変数の表示形式を整えたい場合は、`data-convert` 属性を使用します。これは、プログラムから渡されたデータを、画面に表示する前に指定した形式に変換するためのものです。変換はあくまで表示専用であり、元の値そのものは変更されません。なお、`data-convert` は表示形式の調整に使われるのに対し、`data-filter` は値そのものを変換します。`data-convert` はタグ記法でもコメント記法でも使用できます。

## サンプルコード
`data-convert="date"` 表示前に、日付形式に整形します。たとえば「2025-10-19」などの形式になります。
```html
<div data-loop=$users>
    <!-- $user->created_at->toDateString() data-convert="date" -->
</div>
```

## 利用可能な変換形式一覧
以下の変換形式をご利用いただけます。標準関数や独自のコンバート関数を作成して指定することもできます。`data-convert` を指定しなくても、HTMLの特殊文字などのエスケープがされますが、data-convert="raw"を指定することで入力データをそのまま表示することができます。そのまま表示されるため、セキュリティー脆弱性が発生する可能性がありますので十分ご注意ください。

|data-convertの値|表示結果の例|
|:-----------|:-----------|
|number|30,000（カンマ区切りの数値）|
|number:2|30,000.00（小数点以下2桁付き）|
|hidden|*****（値を伏せて表示）|
|date|2013/12/05（日付形式）|
|date:"Y/m/d H:i:s"|2013/12/05 23:03:04（日時形式）|
|date:"Y.m.d"|2013.12.05（ピリオド区切り）|
|jdate:"m/d(D)"|12/05(木)（日本語の曜日付き）|
|uploadsize|5 KB（ファイルサイズ表示）|
|ellipsis:10|Lorem ipsu...（10文字で省略）|
|raw_download|アップロードファイルへのリンク表示|
|raw_thumbnail|アップロードファイルのサムネイル表示|
|raw_autolink|URLを自動的にリンク化|

# QA
## `data-convert` 属性で独自の変換ロジックを追加したい場合、どのような手順で独自コンバート関数を定義し、テンプレートから呼び出せるようにすればよいでしょうか？
テンプレート内で `data-convert` 属性を使って独自の変換処理を行いたい場合、以下の手順で実装できます。まず、`public` かつ `static` なメソッドを定義します。必要に応じて引数も受け取れます。
```php
public static function unit($str, $parameter1)
{
    return $str.$parameter1;
}
```

テンプレート内で `data-convert` 属性を使い、クラスとメソッドを指定して呼び出します。コロン `:` で区切って引数も渡せます。
```html
<!-- $price data-convert="App\Http\Controllers\Admin\UserController::unit:'円'" -->
```
