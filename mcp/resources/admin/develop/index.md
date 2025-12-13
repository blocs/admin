# 自動生成した管理画面の一覧表示機能をカスタマイズする方法
自動生成で生成した管理画面には、標準で一覧表示機能が組み込まれています。この機能は、テンプレートや関連メソッドを通じて実装されており、プロジェクトの要件に応じてカスタマイズすることが可能です。

## テンプレートの一覧
一覧表示画面は、複数のテンプレートファイルによって構成されており、共通テンプレートは `resources/views/admin/base/index` に格納されています。このディレクトリ内には、画面の各パーツを構成するテンプレートが用意されており、用途ごとに分かれています。

|テンプレート名|用途|
|:-----------|:-----------|
|header.html|画面上部のヘッダー部分|
|footer.html|画面下部のフッター部分|
|pagination.blade.php|ページネーション（ページ送り）|
|search.html|検索フォームの表示|

|テンプレート名|用途|
|:-----------|:-----------|
|index.blocs.html|一覧表示画面のメインテンプレート|

これらのテンプレートは、必要に応じて編集・拡張することで、一覧画面のレイアウトや機能を柔軟にカスタマイズできます。

## ベースコントローラーの一覧表示機能に含まれるメソッド
|メソッド（ルート名）|メソッド（ルート名）|処理内容|
|:-----------|:-----------|:-----------|
|public|index|登録されているデータの一覧を表示|
|public|search|条件を指定してデータを検索し、結果の一覧を表示|
|protected|prepareIndex|出力のためのデータ処理|
|protected|prepareIndexSearch|条件を指定してデータを検索し、条件に従ってソート|
|protected|prepareIndexPaginate|ページネーション処理|
|protected|outputIndex|ビューの出力処理|

## ベースコントローラーで使用できる主な変数
|変数名|説明|
|:-----------|:-----------|
|$this->loopItem|一覧表示で使用するテーブル名|
|$this->paginateNum|1ページあたりの表示件数（0でページングなし）|
|$this->paginateName|ページネーションのパラメータ名（デフォルトは page）|
|$this->searchItems|検索条件の配列|

## 検索窓を設置する方法
テンプレート内で `$searchPlaceholder` を指定することで、検索窓が表示されます。また、`searchFilter` を上書きすることで、追加の検索フォームを自由にカスタマイズして設置することが可能です。さらに、コントローラーの `prepareIndexSearch` メソッドに検索条件に基づいた絞り込み処理を追加することで、検索機能を実装できます。

### サンプルコード（index.blocs.html）
```html
<!-- $searchPlaceholder="Search by code" -->
<!-- data-bloc="searchFilter" -->
<div class="col-4 p-1">
    <div class="input-group input-group-outline">
        <label class="form-label">Year</label>
        <select class="form-control" id="year" name="year">
            <option value="" selected></option>
        </select>
    </div>
</div>
<!-- data-endbloc -->
```

- 検索窓に表示されるプレースホルダー（例：「Search by code」）を指定します。

### サンプルコード（コントローラー）
```php
protected function prepareIndexSearch(&$mainTable)
{
    empty($this->val['year']) || $mainTable->where('year', $this->val['year']);

    foreach ($this->searchItems as $searchItem) {
        $mainTable->where(function ($query) use ($searchItem) {
            $query
                ->where('code', 'LIKE', '%'.$searchItem.'%');
        });
    }
}
```

- `prepareIndexSearch` メソッドでは、検索フォームで入力された値をもとに、データの絞り込み処理を行います。
- 上記の例では、「年度（`year`）」による完全一致検索と、「コード（`code`）」による部分一致検索を実装しています。

## ソート機能の設定方法
一覧画面で項目ごとに ソート機能 を追加するには、テンプレート内で `sortHeader` と `sortHref` を `data-include` 属性で指定します。ソートが不要な項目には、`data-include` を外してください。

### テンプレートで使用できる主な変数
|変数名|説明|
|:-----------|:-----------|
|$sortItem|ソート対象の項目名（例：year）|
|$sortOrder|ソート順序（asc または desc）|
|$sortClass|ソート対象の列に付与するクラス名|

### サンプルコード（index.blocs.html）
```html
<!-- data-include="sortHeader" $sortItem="year" $sortOrder="desc" $sortClass="d-none d-md-table-cell" -->
<th>
    <!-- data-include="sortHref" -->
    <a class="dataTable-sorter">Year</a>
</th>
```

この例では、「`Year`」列に対して降順（`desc`）でソートできるように設定しています。`sortClass` によって、列の表示スタイルも調整可能です。

### サンプルコード（コントローラー）
```php
protected function prepareIndexSearch(&$mainTable)
{
    $this->keepItem('sort');

    // ソート条件の初期化とバリデーション
    if (empty($this->val['sort']) || !is_array($this->val['sort'])) {
        $this->val['sort'] = [];
    } else {
        $this->val['sort'] = array_filter($this->val['sort'], 'strlen');
    }
    
    // ソート条件が空の場合、デフォルト値を設定
    if (empty($this->val['sort'])) {
        $this->val['sort'] = ['year' => 'desc', 'code' => 'asc'];
    }

    // 指定された条件でソート
    $allowedSortItems = ['year', 'code'];
    foreach ($allowedSortItems as $sortItem) {
        if (!empty($this->val['sort'][$sortItem])) {
            $mainTable->orderBy($sortItem, $this->val['sort'][$sortItem]);
        }
    }
}
```

この処理では、ユーザーが指定したソート条件に基づいて、データの並び順を変更します。指定がない場合は、`year` を降順、`code` を昇順で並び替えるようにしています。
