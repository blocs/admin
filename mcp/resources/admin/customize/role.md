# 管理画面のアクセス権限を設定する方法
管理画面のアクセス権限は、JSON形式の定義ファイルを使って設定することができます。ユーザーの役割や操作可能な機能をこのファイルに記述することで、システム全体の権限管理を柔軟に行うことが可能です。本説明では、JSONファイルの構成と、アクセス権限を正しく設定するための手順について解説します。

# ルーティング
`Blocs\Middleware\Role::class` を設定しているルート名と役割を紐つけて、画面を利用できる役割を設定します。以下の `profile` のように、ミドルウェアから `Blocs\Middleware\Role::class` を削除すると、アクセス制限が掛からなくなります。認可設定が不要な時は、削除してください。

```php
Route::middleware(['web', 'auth'])
    ->prefix('admin/profile')
    ->name('profile.')
    ->group(function () {
        ...
    }
    );

Route::middleware(['web', 'auth', Blocs\Middleware\Role::class])
    ->prefix('admin/user')
    ->name('admin.user.')
    ->group(function () {
        ...
    }
    );
```

## 定義ファイルの形式（JSON形式）
アクセス権限の設定は、JSON形式の定義ファイルとして記述します。作成した定義ファイルは、`config/role.php` に保存してください。ここで役割名と、その役割を持つ時にアクセスできるルート名を紐つけます。ルート名の指定には、`*（ワイルドカード）` を使用できます。

### サンプルコード
```php
<?php

return [
    'admin' => [
        'admin.*',
    ],
];
```

ここで定義された役割名は、ユーザー管理の役割に表示されます。ユーザー管理で該当する役割を許可することで、そのルート名にアクセスできるようになります。
