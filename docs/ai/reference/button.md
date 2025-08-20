# ボタン表示

## リンクボタン
- [引数]    [概要]    [Example]
- $buttonHref    リンク先のURL    route('admin.user.create')
- $buttonClass    スタイル    btn btn-primary
- $buttonIcon    アイコン    fa-solid fa-plus
- $buttonLabel    ラベル    新規作成
- 修正方法は、Example を参考にしてください

### Example
```
<!--
    data-include='button_href'
    $buttonHref=route('admin.user.create')
    $buttonClass=‘btn btn-primary'
    $buttonIcon=‘fa-solid fa-plus'
    $buttonLabel=‘新規作成'
-->
```
