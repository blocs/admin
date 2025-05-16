# 一覧画面修正

## 検索条件
- 検索が必要な時は、テンプレートの $searchPlaceholder に検索をうながす文をセットします
- コントローラーの prepareIndexSearch で、検索条件をセットします
- 修正方法は、Example を参考にしてください

### Example
```
<!-- $searchPlaceholder="会社名か証券番号で検索" -->
```

### Example
```php
            $mainTable->where(function ($query) use ($searchItem) {
                $query
                    ->where('name', 'LIKE', '%'.$searchItem.'%')
                    ->orWhere('code', 'LIKE', '%'.$searchItem.'%');
            });
```

## ソート条件
- ソートが必要な時は、テンプレートのテーブルヘッダーの該当項目に sortHeader、sortHref、dataTable-sorter をセットします
- ソートが不要な時は、該当項目の sortHeader、sortHref、dataTable-sorter を削除します
- コントローラーの prepareIndexSearch で、ソート条件をセットします
- 修正方法は、Example を参考にしてください

### Example: ソートが必要な時
```
                            <!-- data-include="sortHeader" $sortItem="name" -->
                            <th>
                                <!-- data-include="sortHref" -->
                                <a class="dataTable-sorter">会社名</a>
                            </th>
```

### Example: ソートが不要な時
```
                            <th>会社名</th>
```

### Example
```php
        count($this->val['sort']) || $this->val['sort'] = ['name' => 'asc'];

        // 指定された条件でソート
        foreach (['name', 'code', 'created_at'] as $sortItem) {
            empty($this->val['sort'][$sortItem]) || $mainTable->orderBy($sortItem, $this->val['sort'][$sortItem]);
        }
```

## 一括選択
- 一括選択が不要な時は、テンプレートの $selectable に false をセットします
- 修正方法は、Example を参考にしてください

### Example
```
<!-- $selectable=false -->
```
