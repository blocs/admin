# 自動生成ツールの定義ファイルを作成する方法
自動生成ツールは、Laravelベースの管理画面を自動生成します。このツールは、あらかじめ作成したJSON形式の定義ファイルをもとに、必要な画面や機能を自動的に構築します。本ドキュメントでは、そのJSON定義ファイルの作成方法について詳しく説明します。

## 定義ファイルの形式（JSON形式）
管理画面の構成情報を、JSON形式の定義ファイルとして記述します。作成した定義ファイルは、`docs/develop/` に保存してください（`docs/develop/sample.json` など）。定義ファイルは、以下の4つの主要なセクションで構成されています。

|キー項目|説明|
|:-----------|:-----------|
|controller|コントローラーに関する設定|
|form|入力フォームの定義|
|menu|メニューの表示設定|
|route|ルーティングの設定|

## コントローラーに関する設定項目
|キー項目|説明|例|
|:-----------|:-----------|:-----------|
|controllerName|コントローラーのクラス名を指定します|Admin/SampleController|
|viewPrefix|関連するビューのパスを指定します（ドット区切り）|admin.sample|
|modelName|使用するモデルの名前を指定します|Admin/Sample|
|loopItem|一覧表示などで使用するテーブル名を指定します|samples|
|paginateNum|1ページに表示する件数を指定します（0の場合はページングなし）|20|
|noticeItem|操作完了時のメッセージに使用する項目名（例：登録名など）|name|

## 入力フォームの定義項目
入力フォームは、フォーム名をキーとした連想配列で定義します。各フォームには、以下の項目を連想配列形式で設定します。

### 入力タイプ一覧
|type|説明|
|:-----------|:-----------|
|text|1行テキスト入力|
|textarea|複数行テキスト入力|
|datepicker|日付入力|
|timepicker|日付＋時間入力|
|number|数値入力|
|select|プルダウン選択|
|radio|ラジオボタン|
|checkbox|チェックボックス|
|select2|検索機能付きプルダウン|
|upload|ファイルアップロード|

### テキスト入力タイプの定義
対象タイプ：`type=text、textarea、datepicker、timepicker、number`
|キー項目|説明|記述例|
|:-----------|:-----------|:-----------|
|type|入力タイプ|text|
|label|表示ラベル|名前|
|required|必須入力かどうか|true|
|placeholder|プレースホルダー（入力例）|入力してください|
|disabled|入力不可にするか|false|
|readonly|読み取り専用にするか|false|

### 選択入力タイプの定義
対象タイプ：`type=select、radio、checkbox`
|キー項目|説明|記述例|
|:-----------|:-----------|:-----------|
|type|入力タイプ|checkbox|
|label|表示ラベル|仕事|
|required|必須入力かどうか|true|
|option|選択肢（値とラベルの連想配列）|"1": "学生", "2": "会社員"|
|default|初期選択値|[1, 2]|

選択肢のラベルにはHTMLタグの使用が可能です。

### 検索できるプルダウンの定義
対象タイプ：`type=select2`
|キー項目|説明|記述例|
|:-----------|:-----------|:-----------|
|type|入力タイプ|select2|
|label|表示ラベル|仕事|
|option|選択肢（値とラベルの連想配列）|"1": "学生", "2": "会社員"|
|default|初期選択値|[1, 2]|
|required|必須入力かどうか|true|
|multiple|複数選択可能か|true|

選択肢のラベルにはHTMLタグの使用が可能です。

### ファイルアップロードの定義
対象タイプ：`type=upload`
|キー項目|説明|記述例|
|:-----------|:-----------|:-----------|
|type|入力タイプ|upload|
|label|表示ラベル|アップロードファイル|
|required|必須入力かどうか|true|
|multiple|複数ファイルのアップロード可否|true|

## メニューの表示設定項目
メニューの表示に関する設定は、以下の項目で構成されます。
|設定項目|説明|記述例|
|:-----------|:-----------|:-----------|
|name|メニューがリンクするルート名（ルーティング名）を指定します|admin.sample.index|
|lang|メニューに表示するラベル（日本語など）を指定します|サンプル|
|icon|メニューに表示するアイコン（Font Awesomeのクラス名）を指定します|fa-cogs|
|role|表示制御に使用するロール（権限）設定。trueの場合、ロールに応じて表示が制限されます|true|

## ルーティングの設定項目
ルーティングの設定では、コントローラーに対応するURLやルート名の共通部分を定義します。
|設定項目|説明|記述例|
|:-----------|:-----------|:-----------|
|routePrefix|URLの共通パスを指定します。コントローラーのルートに対応します|admin/sample|
|routeName|ルート名の共通プレフィックスを指定します。ビューやリンク生成時に使用されます|admin.sample.|

## サンプルコード
### 企業情報の管理画面定義
- controller
  - CompanyController を使用し、ビューは admin.company に配置。
  - モデルは Company、一覧表示は companies テーブル。
  - ページネーションは 1ページ20件、通知メッセージには name を使用。
- form
  - code: 証券番号（テキスト入力、必須）
  - name: 名前（テキスト入力、必須）
- menu
  - メニュー名は「企業」、アイコンは fa-building（Font Awesome）
- route
  - URLは /admin/company、ルート名は admin.company. をプレフィックスとして使用。

```json
{
    "controller": {
        "controllerName": "CompanyController",
        "viewPrefix": "admin.company",
        "modelName": "Company",
        "loopItem": "companies",
        "paginateNum": 20,
        "noticeItem": "name"
    },
    "form": {
        "code": {
            "label": "証券番号",
            "type": "text",
            "required": true
        },
        "name": {
            "label": "名前",
            "type": "text",
            "required": true
        }
    },
    "menu": {
        "name": "admin.company.index",
        "lang": "企業",
        "icon": "fa-building"
    },
    "route": {
        "routePrefix": "admin\/company",
        "routeName": "admin.company."
    }
}
```

### ソリューション管理画面定義
- controller
  - コントローラーは Admin/SolutionController、ビューは admin.solution。
  - モデルは Admin/Solution、一覧表示は solutions テーブル。
  - ページネーションは 20件、通知メッセージには name を使用。
- form
  - disabled_at: ステータス（ラジオボタン、HTMLラベル付き、有効／無効）
  - name: 名前（テキスト入力、必須）
  - pattern: パターン選択（プルダウン、パターンA/B）
- menu
  - メニュー名は「ソリューション」、アイコンは fa-tag、ロール制御あり（role: true）
- route
  - URLは /admin/solution、ルート名は admin.solution. をプレフィックスとして使用。

```json
{
    "controller": {
        "controllerName": "Admin\/SolutionController",
        "viewPrefix": "admin.solution",
        "modelName": "Admin\/Solution",
        "loopItem": "solutions",
        "paginateNum": 20,
        "noticeItem": "name"
    },
    "form": {
        "disabled_at": {
            "label": "ステータス",
            "type": "radio",
            "option": [
                "<span class='badge text-bg-success me-2'>有効<\/span>",
                "<span class='badge text-bg-warning me-2'>無効<\/span>"
            ],
            "default": "1",
            "required": true
        },
        "name": {
            "label": "名前",
            "type": "text",
            "required": true
        },
        "pattern": {
            "label": "パターン",
            "type": "select",
            "option": [
                "パターンA",
                "パターンB"
            ],
            "required": true
        }
    },
    "menu": {
        "name": "admin.solution.index",
        "lang": "ソリューション",
        "icon": "fa-tag",
        "role": true
    },
    "route": {
        "routePrefix": "admin\/solution",
        "routeName": "admin.solution."
    }
}
```

# 管理画面の自動生成後の不要な機能をなくす方法
自動生成ツールは、Laravelベースの管理画面を自動生成します。自動生成した管理画面には、一覧表示・検索・登録・編集・削除などの基本機能が一括で組み込まれますが、プロジェクトによっては不要な機能も含まれる場合があります。不要な機能をなくすことは、セキュリティやユーザー体験の向上、保守性の確保のためにも重要です。以下は、自動生成ツールの実行後に必ず行うべき手順です。

## ファイル操作機能を無効にする方法
定義ファイル内で入力タイプに `upload` が含まれていない場合、ファイル操作機能は不要ですので無効化します。

### ルーティングの修正
ファイル操作機能を完全に無効にするには、`routes/web.php` から以下のルーティング定義を削除してください。
- upload
- download
- thumbnail

## 一括削除機能を無効にする方法
一覧画面で複数行を選択して削除できる **一括削除機能** を無効にするには、以下の対応が必要です。

### ルーティングの修正
`routes/web.php` から、以下のルーティング定義を削除してください。
- select

### テンプレートの修正
一覧画面のテンプレートファイル `index.blocs.html` を編集し、`$selectable` によって制御されている以下のような出力部分をすべて削除します。
```html
<!-- data-bloc="buttonBottomLeft" -->
    <!--
        data-include="modal_destroy"
        $buttonClass="btn btn-danger"
        $modalAction=route(prefix().".select")
        data-if="$isLoop && !empty($selectable)"
    -->
<!-- data-endbloc -->
```

## ステータス切り替え機能を無効にする方法
定義ファイルのフォーム項目に `disabled_at` が含まれていない場合、ステータスの切り替え機能は不要ですので無効化します。

### ルーティングの修正
`routes/web.php` から、以下のルーティング定義を削除します。
- toggle

### テンプレートの修正
一覧画面のテンプレートファイル `index.blocs.html` を編集し、以下のような `modalActivate` および `modalInactivate` に関連する処理をすべて削除してください。
```html
<a class="me-3" data-bs-toggle="modal" data-bs-target="#modalActivate" :data-modalaction=route(prefix().".toggle", ["id" => $user->id]) data-exist=$user->disabled_at>
    <i class="fa-solid fa-check" data-bs-toggle="tooltip" :data-bs-original-title=lang("template:admin_user_valid_title")></i>
</a>
<a class="me-3" data-bs-toggle="modal" data-bs-target="#modalInactivate" :data-modalaction=route(prefix().".toggle", ["id" => $user->id]) data-none=$user->disabled_at>
    <i class="fa-solid fa-ban" data-bs-toggle="tooltip" :data-bs-original-title=lang("template:admin_user_invalid_title")></i>
</a>

<!-- data-bloc="modalFooterRight" -->
    <!--
        data-include="buttonSuccessModal"
        $buttonModalLabel=lang("template:admin_user_button_valid")
    -->
<!-- data-endbloc -->
<!--
    data-include="modal"
    $modalName="modalActivate"
    $modalTitle=lang("template:admin_user_valid_title")
    $modalMessage=lang("template:admin_user_valid_message")
    data-exist=$isLoop
-->

<!-- data-bloc="modalFooterRight" -->
    <!--
        data-include="buttonWarningModal"
        $buttonModalIcon="fa-solid fa-ban"
        $buttonModalLabel=lang("template:admin_user_button_invalid")
    -->
<!-- data-endbloc -->
<!--
    data-include="modal"
    $modalName="modalInactivate"
    $modalTitle=lang("template:admin_user_invalid_title")
    $modalMessage=lang("template:admin_user_invalid_message")
    data-exist=$isLoop
-->
```

### モデルの修正
自動生成したモデルファイルから、以下のアクセサ・ミューテータを削除してください。
```php
public function getDisabledAtAttribute($value)
{
    return isset($value) ? 1 : 0;
}

public function setDisabledAtAttribute($value)
{
    $this->attributes['disabled_at'] = empty($value) ? null : now();
}
```

# QA
## 入力フォームを必須項目にしたい。
以下のように記述することで、`name` フィールドを必須項目としてバリデーションできます。
```html
<input type="text" name="name" />
<!-- !name="required" data-lang="必須入力です。" -->
@error("name") <div class="invalid-feedback">{{ $message }}</div> @enderror
```
