# 動的にメニュー項目を追加する方法
テンプレートで使用するメニュー項目は、コントローラー側から `addOption` メソッドを使って動的に追加できます。これにより、表示される内容や選択肢を柔軟に変更でき、ユーザーの入力や状況に応じたインターフェースを構築することが可能になります。

## サンプルコード
以下のように、`addOption` にキーとラベルの配列を渡すことで、メニュー項目を追加できます。
```php
addOption("type", ["foreign" => "外国のお客様"]);
```

`select` 要素の場合は、静的に指定したメニュー項目の後に、動的なメニュー項目が自動で追加されます。以下はテンプレートのサンプルです。
```html
<div class="input-group input-group-outline">
    <label class="form-label">Category</label>
    <select class="form-control" required id="category" name="category">
        <option value=""></option>
    </select>
</div>
```

`radio` や `checkbox` 要素に動的なメニュー項目を追加するには、`data-loop` 属性を使います。この属性には、表示したい項目の配列（例：`$option_category`）を指定します。各項目は、配列の中の `$value`（値）と `$label`（表示名）を使って生成されます。以下は `checkbox` を使ったテンプレートのサンプルです

```html
<div class="row">
    <label>Category</label>
    <div class="col-auto p-1" data-loop=$option_category>
        <div class="form-check">
            <label class="form-check-label">
                <input class="form-check-input" type="checkbox" name="category[]" :value=$value /> <!-- $label -->
                </label>
        </div>
    </div>
</div>
```

## 複数のメニュー項目をまとめて追加する方法
配列に複数のキーとラベルを指定することで、複数のメニュー項目を一度に追加できます。
```php
addOption("type", [
    "foreign" => "外国のお客様",
    "vip"     => "VIPのお客様",
    "guest"   => "ゲスト",
]);
```

この方法を使えば、テンプレート側でメニュー項目を固定せず、コントローラーのロジックに応じて柔軟に選択肢を変更できます。

## メニュー項目をグルーピングする方法
`addOption` に 連想配列の中にさらに連想配列 を渡すことで、選択肢をグループごとにまとめて表示することができます。これにより、プルダウンメニュー内でカテゴリ分けされた選択肢を作成できます。
```php
$select1 = [
    "guest"   => "ゲスト",
];

$select2 = [
    "foreign" => "外国のお客様",
    "vip"     => "VIPのお客様",
];

addOption("type", [
    "一般" => $select1,
    "関係者" => $select2,
]);
```

表示のイメージです。このようにグルーピングすることで、選択肢が整理され、ユーザーが目的の項目を見つけやすくなります。

- 一般
  - ゲスト
- 関係者
  - 外国のお客様
  - VIPのお客様

## 回答例
- **user**
検索できるプルダウンメニューを作成したい。
- **assistant**
`ai-select2` クラスを使えば、検索機能付きのプルダウンメニューを簡単に実装できます。選択肢が多い場合でも、ユーザーがキーワードで絞り込めるので便利です。
```html
<select name="pulldown" class="form-control ai-select2">
</select>
```
- **assistant**
コントローラー側で `addOption` を使えば、選択肢を動的に追加することも可能です。これにより、状況に応じて柔軟にメニュー内容を変更できます。
---
- **user**
複数選択できるプルダウンメニューを作成したい。
- **assistant**
複数選択できるプルダウンメニューは、`ai-select2` クラスを使うことで簡単に実装できます。このクラスを使うと、選択肢が多い場合でもキーワード検索で絞り込みができるため、ユーザーにとって使いやすくなります。
- **assistant**
複数選択を可能にするには、`multiple` 属性を指定します。選択された値を配列で受け取るために、`name` 属性は `pulldown[]` のように記述します。
```html
<select name="pulldown[]" class="form-control ai-select2" multiple>
</select>
```
- **assistant**
コントローラー側で `addOption` を使えば、選択肢を動的に追加することも可能です。これにより、状況に応じて柔軟にメニュー内容を変更できます。
---
