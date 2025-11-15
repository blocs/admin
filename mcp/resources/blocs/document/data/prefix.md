# 表示する変数に文字列を追加する方法
テンプレート内で、変数の表示内容に文字列を追加したい場合は、`data-prefix` や `data-postfix` 属性を使用します。`data-prefix` は、指定された値が存在すると判断されたときに、変数の先頭に文字列を追加します。同様に、`data-postfix` は、値が存在すると判断されたときに、変数の末尾に文字列を追加します。これらの属性は、タグ記法とコメント記法の両方で使用できます。

ここで「存在する」とは、`data-val` に指定された値が空でないことを意味し、`0` のような値も「存在する」とみなされます。また、`data-assign` と併用することで、文字列が追加された値を新しい変数に代入することができます。さらに、`data-attribute` と組み合わせることで、文字列が追加された値を HTML タグの属性値として設定することも可能です。

## サンプルコード
`$price` に値がある場合、「¥」を先頭に追加して表示します。
```html
<div $price data-prefix="¥">無料</div>
```

`$value` に値がある場合、`$value` の先頭に `¥` を追加して `$price` に代入します。
```html
<!-- data-assign=$price data-val=$value data-prefix="¥" -->
<div $price>無料</div>
```

`$url` に値がある場合、「http://」が先頭に追加され、`href` 属性に設定されます。
```html
<!-- :href=$url data-prefix="http://" -->
<a>Google</a>
```

## サンプルコード
`$price` に値がある場合、「円」が末尾に追加されて表示されます。
```html
<div $price data-postfix="円">無料</div>
```
