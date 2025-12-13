# グラフを表示する方法
Auto Include 機能を利用することで、テンプレート内にグラフを簡単に表示できます。この機能を使えば、複雑な設定を行うことなく、視覚的に情報を伝えるグラフ（棒グラフ、折れ線グラフ）を手軽に組み込むことが可能です。

## 定義済みグラフのブロック
グラフに表示するデータは、コントローラー側で事前に準備しておきます。

|ブロック名|用途|
|:-----------|:-----------|
|chartjs_1line|折れ線グラフ|
|chartjs_2bar1line|2種類の棒グラフと、それらの累積を示す折れ線グラフ|

## グラフのカスタマイズ方法（変数の使用）
テンプレート内で `data-include` 属性に **引数（変数）** を指定することで、グラフの色や凡例（ラベル）、軸の設定などを柔軟にカスタマイズできます。

|引数|説明|
|:-----------|:-----------|
|$graphId|グラフの一意な識別子（ID）|
|$chartjsColor|グラフの色設定|
|$graphLegendLine1|凡例（折れ線グラフのラベル）|
|$graphLabels|X軸に表示するラベル|
|$graphYaxisMax|Y軸の最大値|
|$graphDataLine1|折れ線グラフのデータ|

### テンプレートのサンプルコード
```html
<!--
    data-include="chartjs_1line"
    $graphId="graphUser"
    $chartjsColor="Blues3"
    $graphLegendLine1="登録"
-->

<canvas :id=$graphId class="chart-canvas" height="300"></canvas>
```

### コントローラーのサンプルコード
```php
$this->val['graphLabels'] = json_encode($xaxis);
$this->val['graphDataLine1'] = json_encode($line1);
$this->val['graphYaxisMax'] = $yaxisMax;

$view = view($this->viewPrefix.'.index', $this->val);
return $view;
```
