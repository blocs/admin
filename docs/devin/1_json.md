こんにちは @Devin
blocs/test-devin repo にアクセスしてください
リクエストに従って仕事をしてください

# リクエスト
- 要件に従って対象ファイルを作成してください
- 参考ファイルを参考にしてください
- 実装追加した変更で PR を作ってください
- PRのタイトルとサマリは日本語にしてください

## 参考ファイル
- docs/devin/reference/json.md

# 要件

## 対象ファイル
- docs/develop/company.json

## controller
- ビューは、admin.company
- ページングは、20件ごと
- メッセージの表示項目は、name
- その他項目は適切に設定してください

## form
- 項目は、証券番号、会社名、会社タイプ
- 会社タイプは select で、選択肢は、1:株式会社、2:有限会社、3:合同会社
- 必須項目はなし

## menu
- roleにかかわらず、常に表示

## route
- URLは、/company
