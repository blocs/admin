A PHP template engine based on HTML
-----

BlocsはPHPで動作するテンプレートエンジンです。
テンプレートエンジンとは、プログラムで作成されたデータとデザインのためのテンプレート（ビュー）を紐つけてHTMLを生成するライブラリです。テンプレートエンジンを使うことで、プログラムとテンプレートを分離することができます。

ロジック（プログラム）とデザイン（HTML/CSS）を分離して疎な関係にすることで、プログラマーとコーダーのお互いのソース変更や開発の遅れなどの影響を最小限にし、効率的な開発、維持を行うことができます。

## 理念
- HTMLと同様にあつかえる（HTMLとの親和性）
- 直感的でシンプルに記述できる
- 入力フォームと一緒にバリデーションが設定できる

## HTMLとの親和性
Blocsは、データとテンプレートの紐つけにデータ属性を使用します。
データ属性は特別なタグなどではなく、通常のタグに属性を追加するだけですので、普通のHTMLと同様に扱うことができます。
すなわち、オーサリングツールで作成したHTMLをそのままテンプレートとして使用でき、またオーサリングツールを使ってテンプレートを変更できるということです。

Blocsでの記述例:
```html
<html>
<div data-val=$message>メッセージ</div>
</html>
```

## 直感的でシンプルな記述
タグにデータ属性を追加することで、プログラムより渡されたデータを表示したり、条件にしたがって表示/非表示を制御することができます。
データ属性を組み合わせることで、非常にシンプルにデータとテンプレートの紐つけができます。

また、データ属性は4種類しかありません。
学習コストが小さいので、プログラムがわからないデザイナーやコーダーでもテンプレートの作成、変更ができます。
デザインに変更が入るたびにプログラマーにテンプレートの変更を依頼しなくてもよくなります。

代表的なテンプレートエンジンでの記述例:
```html
<html>
<ul>
	{foreach from=$list item=data}
	<li>
		<div>{$data.name}</div>
		<div>{$data.age}</div>
	</li>
	{/foreach}
</ul>
</html>
```

Blocsでの記述例:
```html
<html>
<ul>
	<li data-repeat=$list>
		<div data-val=$name>田中太郎</div>
		<div data-val=$age>19</div>
	</li>
</ul>
</html>
```

## バリデーション
Blocsなら面倒な入力値のチェック処理（バリデーション）も簡単です。データ属性でチェック条件と、エラーメッセージが設定できます。また、BlocsはjQuery Validation Engineと連携して、JavaScriptによるリアルタイムでのバリデーションもできます。ユーザーを待たせず画面遷移なしで、入力画面で即時に入力データをチェックできます。

さらに、Blocsはメニュー項目の入力値チェックをします。メニュー項目にない値が入力された時にはエラーを表示されますので、不正なデータ入力を防げます。そして、正しいデータはメニューのラベルを自動で取得して変換しますので、入力データの確認画面などで面倒な値の変換処理を作る必要はありません。

テンプレート（メニュー入力画面）:
```
<html>

<span data-val=$type>メニュー項目</span>

<form action='./' method='post'>
<select id="type" name="type">
<option value="company">法人のお客様</option>
<option value="private">個人のお客様</option>
<option value="other">その他</option>
</select>
<div class="input_error" data-form="type" data-validate="required">必須入力です</div>
<input type='submit' />
</form>

</html>
```

表示結果:  
3行目: メニュー項目のラベル（個人のお客様）を表示
```html
<html>

<span>個人のお客様</span>

<form action='./' method='post'>
<select id="type" name="type">
<option value="company" >法人のお客様</option>
<option value="private" selected>個人のお客様</option>
<option value="other" >その他</option>
</select>

<input type='submit' />
</form>

</html>
```
