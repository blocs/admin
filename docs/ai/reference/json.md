# JSONファイル作成
- JSONファイルには、controller, form, menu, routeの4つの大項目があります
- 作成方法は、Example を参考にしてください

## controller	コントローラー設定
- [項目定義]	[説明]	[Example]
- controllerName	コントローラー名	TestController
- viewPrefix	ビューの場所	test.admin
- modelName	モデル名	Test
- loopItem	テーブル名	tests
- paginateNum	1ページの行数	20（0: ページングなし）
- noticeItem	メッセージに使うキー項目	name

## form	入力項目設定
- [項目定義]	[説明]	[Example]
- [key]	フォーム名	name
- label	項目名	名前
- type	項目タイプ
  - text	1行テキスト
  - textarea	複数行テキスト
  - datepicker	日付入力
  - timepicker	日付時間入力
  - upload	ファイルアップロード
  - number	数値入力
  - select	プルダウン
  - select2	複数選択できるプルダウン
  - radio	ラジオボタン
  - checkbox	チェックボックス
    - option	選択肢	"1": "学生", "2": "会社員"
    - default	初期値	[0, 1]
    - required	必須入力	true
    - multiple	複数選択	true

## menu	メニュー設定
- [項目定義]	[説明]	[Example]
- name	ルート名	admin.test.index
- lang	メニュー名	テスト
- icon	アイコン https://fontawesome.com/icons で選択	fa-cogs
- role	roleで表示を制御する	true

## route	ルーティング設定
- [項目定義]	[説明]	[Example]
- routePrefix	共通URL	test\/admin
- routeName	共通ルート名	admin.test.

## Example
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

## Example
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