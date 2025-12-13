# ログイン画面をカスタマイズする方法
開発支援パッケージには、標準でログイン画面が組み込まれており、ユーザー認証に必要な基本機能があらかじめ実装されています。プロジェクトの要件に応じて、画面のデザインや認証処理を柔軟にカスタマイズすることが可能です。

## テンプレートの一覧
ログイン画面は、複数のテンプレートファイルによって構成されており、デザインのパターンに応じて使い分けることができます。

|テンプレート名|用途|
|:-----------|:-----------|
|include/upper.html|画像が画面の上部に配置されたレイアウト|
|include/background.html|画像を背景として表示するレイアウト|
|include/left.html|画像が画面の左側に配置されたレイアウト|
|include/form.html|ログインフォームの部品テンプレート|
|login.blocs.html|ログイン画面のメインテンプレート|

## ログイン画面のデザインを変更する方法
ログイン画面のデザインは、テンプレート内で `data-include` を指定することで切り替えることができます。たとえば、背景に画像を表示するデザインに変更したい場合は、以下のように記述します。
```html
<!-- data-include="include/background.html" -->
```

このようにすることで、`include/background.html` テンプレートが読み込まれ、背景画像付きのログイン画面が表示されます。

# 新しいログイン画面を追加する方法
このパッケージには、管理者向けのログイン画面があらかじめ含まれています。メンバー向けのログイン画面を追加したい場合は、以下の手順に従って設定を行います。管理者画面とは別に、メンバー専用の認証処理や画面レイアウトを用意することで、役割に応じたログイン機能を実現できます。

## 新しいログイン画面の作成手順
1. 新しい **Guard** を作成する
   - Laravel の config/auth.php に Guard を追加します。Guard はユーザーの種類（例：管理者、メンバーなど）に応じた認証を行うための設定です。
2. ログイン用コントローラーを作成する
   - 既存の `app/Http/Controllers/Admin/LoginController.php` をコピーして、新しいコントローラー（例：Login2Controller）を作成します。
3. ログイン後・ログアウト後の遷移先を変更する
```php
// ログイン後の遷移先
$this->redirectTo = '/home2';

// ログアウト後の遷移先
return redirect('/login2');
```

4. Guard を指定する
   - 新しい Guard を使うように、コントローラー内で `Auth::guard()` を指定します。
```php
protected function guard()
{
  return Auth::guard('member');
}
```

5. ルーティングを設定する
   - 新しいログイン画面用のルートを追加します。
```php
Route::middleware(['web'])
    ->group(function () {
        Route::get('/login2', [Login2Controller::class, 'showLoginForm'])->name('login2');
        Route::post('/login2', [Login2Controller::class, 'login2']);
        Route::match(['get', 'post'], '/logout2', [Login2Controller::class, 'logout2'])->name('logout');
    }
    );
```

## 新しいログイン画面の認証設定
1. Guard による認証をルートに追加する
   - 認証が必要なルートに、対象の Guard を指定します。
```php
Route::middleware(['web', 'auth:member'])
```
 
2. 認証失敗時のリダイレクト先を設定する
   - `bootstrap/app.php` に以下のような設定を追加し、Guard による認証失敗時の遷移先を制御します。
```php
->withMiddleware(function (Middleware $middleware): void {
  Authenticate::redirectUsing(function (Request $request): ?string {
    return $request->is('home2') ? '/login2' : null;
  });
})
```
