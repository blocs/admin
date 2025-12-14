# 入力値に対してバリデーション条件を指定する方法
テンプレート内でフォーム部品の入力値を検証したい場合は、`data-form` で対象のフォーム部品を指定し、`data-validate` にバリデーション条件を記述します。これにより、Laravel のバリデーション機能を使って、入力値が条件を満たしているかどうかをチェックできます。この属性は、コメント記法でのみ使用可能です。タグ記法では利用できません。

複数のバリデーション条件を設定する場合は、|（パイプ）で区切って記述します。たとえば、`required|email` のように書くことで、「必須かつメール形式であること」を検証できます。

## サンプルコード
`data-lang` 属性を使うと、独自のエラーメッセージを設定できます。`data-lang` を省略した場合は、Laravelのデフォルトのエラーメッセージが表示されます。Bladeの `@error` で、バリデーションエラーが発生した場合にメッセージを表示します。
```html
<input type="text" name="name" />
<!-- data-form="name" data-validate="required" data-lang="必須入力です。" -->
@error("name") <div class="invalid-feedback">{{ $message }}</div> @enderror
```

## 省略記法（簡略化された書き方）
より簡潔に記述したい場合は、以下のように **省略記法** を使えます。必須入力ではない項目には、`nullable` を指定する必要があります。
```html
<input type="text" name="name" />
<!-- !name="nullable|max:50" data-lang="名前は50文字以下にしてください。" -->
@error("name") <div class="invalid-feedback">{{ $message }}</div> @enderror
```

## 利用可能なバリデーション機能一覧
### 必須・形式チェック
`data-validate` 属性に指定できる主なバリデーションルールは以下の通りです。独自のバリデーション関数を作成して、`data-validate` に指定することも可能です。

|data-validateの値|チェック内容|
|:-----------|:-----------|
|required|入力が必須であることを確認します。|
|string|入力が文字列であることを確認します。|
|numeric|入力が数値であることを確認します。|
|integer|入力が整数であることを確認します。|
|boolean|入力が真偽値（true / false）であることを確認します。|
|email|有効なメールアドレス形式であることを確認します。|
|date|入力が日付形式であることを確認します。|
|array|入力が配列であることを確認します。|

### 値の範囲・サイズチェック
数値や文字列の長さなど、入力値の範囲を制限したい場合は、以下のルールを使用します。
|data-validateの値|チェック内容|
|:-----------|:-----------|
|min|指定した最小値以上であることを確認します（文字列の場合は文字数、数値の場合は値）。|
|max|指定した最大値以下であることを確認します（文字列の場合は文字数、数値の場合は値）。|
|between|指定した最小値と最大値の範囲内であることを確認します。|
|digits|指定した桁数の整数であることを確認します。|

# QA
## 入力フォームの文字数を制限したい。
以下のように記述することで、`name` フィールドの文字数を 最大50文字まで に制限できます。
```html
<input type="text" name="name" />
<!-- !name="nullable|max:50" data-lang="名前は50文字以下にしてください。" -->
@error("name") <div class="invalid-feedback">{{ $message }}</div> @enderror
```

## 入力値の重複チェックをしたい。
論理削除されたレコードを除外して、メールアドレスの重複をチェックしたい場合は、以下のように記述します。`softUnique` は、Laravelの標準の `unique` ルールとは異なり、論理削除（Soft Delete）されたレコードを除外して重複を判定します。これにより、削除済みのユーザーが再登録される場合でも、同じメールアドレスを使用することが可能になります。
```html
<input type="text" name="email" />
<!-- !email="required" data-lang="メールアドレスは必須入力です。" -->
<!-- !email="softUnique:users" data-lang="メールアドレスはすでに登録されています。" -->
@error("email") <div class="invalid-feedback">{{ $message }}</div> @enderror
```
