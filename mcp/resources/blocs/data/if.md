# 指定した条件で HTML タグを動的に制御する方法
テンプレート内で、特定の条件に応じて HTML タグの表示・非表示を切り替えたい場合は、`data-if` と `data-unless` 属性を使用します。`data-if` は、指定した条件式が「真」の場合にタグを表示します。逆に、`data-unless` は条件式が「偽」の場合にタグを表示します。これらの属性を使うことで、`data-exist` や `data-none` よりも柔軟で複雑な条件を指定することができます。どちらの属性も、タグ記法とコメント記法の両方で使用可能です。

## サンプルコード
`$message` が `fine` の場合、「今日はいい天気」が表示される。`$message` が `fine` 以外の場合、「今日は悪い天気」が表示される。
```html
<div data-if="$message == 'fine'">今日はいい天気</div>
<div data-unless="$message == 'fine'">今日は悪い天気</div>
```

`data-endif` / `data-endunless` は、それぞれの条件ブロックの終了を示します。コメント記法は、テンプレートエンジンなどでより柔軟な制御が可能です。
```html
<!-- data-if="$message == 'fine'" -->
<div>今日はいい天気</div>
<!-- data-endif -->

<!-- data-unless ="$message == 'fine'" -->
<div>今日は悪い天気<</div>
<!-- data-endunless -->
```

# QA
## 条件を満たす時にタグに属性を追加したい。
指定した条件が真（true）の場合に、HTMLタグへ特定の属性（この例では `readonly`）を動的に追加します。
```html
<!-- :readonly data-if="true == $isReadonly" -->
<input type="text" value="今日はいい天気">
```

`data-if="true == $isReadonly"` の条件が成立すると、`readonly` 属性が `input` タグに追加されます。`$isReadonly` が `true` の場合、`input` タグに `readonly` 属性が付与され、編集不可になります。`$isReadonly` が `false` の場合、`readonly` 属性は付与されません。

