# テーブルタイプの入力画面を作成する方法
新規登録や編集画面から、複数のレコードを一括で登録できる「テーブルタイプ」の入力画面の作成方法について説明します。

## テーブルタイプの入力画面
複数のレコード（データ）を一度に入力できる画面を「テーブルタイプの入力画面」と呼びます。大量のデータをまとめて入力したい場合にとても便利です。

## 入力フォームのテンプレートを作成する
テーブルタイプの入力画面は、`data-loop` 属性を使って作成します。
まず、1件分の入力フォームのテンプレートを作成し、それを `data-loop` で囲むことで、複数件の入力が可能になります。
```html
<tbody data-loop=$books>
    <tr>
        <th>
            <label for="name">名前</label> *
        </th>
        <td>
            <input type='text' class='form-control' value='' id="name" name="name" required />
        </td>
    </tr>
```
このように、`<tbody>` タグに `data-loop` 属性を付けることで、`$books` のデータ件数分、入力行が繰り返し表示されます。

## バリデーション条件を設定する
バリデーションでは、ワイルドカード（*）を使って複数の入力項目を一括で指定できます。テーブルタイプの入力フォームでは、各入力値が配列として扱われるため、エラーメッセージを表示する際には、現在のループのインデックス（`$loop->index`）を使って添字を指定する必要があります。
```html
<!-- :class="input-group input-group-outline is-invalid" data-exist=$errors->has("books.".$loop->index.".name") -->
<div class="input-group input-group-outline">
    <label class="form-label">名前 *</label>
    <input class="form-control" type="text" id="name" name="name" />

    <!-- !books.*.name='required_with:books.*.price' data-lang='価格を入力してください' --> 
    @error("books.".$loop->index.".name") <div class="invalid-feedback">{{ $message }}</div> @enderror
</div>
```
この例では、`books.*.name` に対して「`books.*.price` が入力されている場合は必須」というバリデーションルールを設定しています。エラーが発生した場合は、該当する行にエラーメッセージが表示されます。

## コントローラーで初期データを作成する
テーブルタイプの入力フォームを表示するには、コントローラー側で初期データを用意しておく必要があります。初期データがないと、画面に入力フォームが表示されません。以下のように記述することで、初期状態で5件分の空の入力フォームを表示できます。
```php
protected function prepareCreate()
{
    if (!old('books')) {
        $minRows = 5;
        $this->val['books'] = [];
        for ($rowNum = 0;  $rowNum < $minRows; $rowNum++) {
            $this->val['books'][] = [];
        }
    }
}
```
このコードは、過去の入力（`old('books')`）が存在しない場合に、空のレコードを5件分用意する処理です。

## 入力されていないデータを除外する方法
`validateStore()` で、フォームなどから送信された「books（本の情報）」の配列を検証し、空のデータを除外するための処理を行っています。
```php
protected function validateStore()
{
    $books = $this->request->input('books', []);

    if (is_array($books)) {
        $books = array_values(array_filter($books, function ($book) {
            if (! is_array($book)) {
                return true;
            }

            $filled = array_filter($book, function ($value) {
                if (is_string($value)) {
                    $value = trim($value);
                }

                return $value !== null && $value !== '';
            });

            return ! empty($filled);
        }));

        $this->request->merge(['books' => $books]);
    }

    parent::validateStore();
}
```

## 入力フォームからの値を登録する
テーブルタイプの入力フォームでは、複数件の入力値が配列として送信されます。そのため、コントローラーやモデルでデータを登録する際は、配列をループ処理して1件ずつ保存します。
```php
protected function executeStore($requestData = [])
{
    $books = $requestData['books'] ?? [];

    if (! is_array($books) || empty($books)) {
        return;
    }

    DB::transaction(function () use ($books) {
        foreach ($books as $book) {
            if (! is_array($book)) {
                continue;
            }

            $model = Book::create($book);
        }
    });
}
```
