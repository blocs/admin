<?php

use App\Http\Controllers\Admin\HomeController;
use App\Http\Controllers\Admin\LoginController;
use App\Http\Controllers\Admin\ProfileController;
use App\Http\Controllers\Admin\UserController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web'])
    ->group(function () {
        Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
        Route::post('/login', [LoginController::class, 'login']);
        Route::match(['get', 'post'], '/logout', [LoginController::class, 'logout'])->name('logout');
    }
    );

Route::middleware(['web', 'auth'])
    ->group(function () {
        Route::get('/home', [HomeController::class, 'index'])->name('home');
    }
    );

Route::middleware(['web', 'auth'])
    ->prefix('admin/profile')
    ->name('profile.')
    ->group(function () {
        Route::get('/{id}/edit', [ProfileController::class, 'edit'])->where('id', '[0-9]+')->name('edit');
        Route::post('/{id}', [ProfileController::class, 'update'])->where('id', '[0-9]+')->name('update');
        Route::post('/upload', [ProfileController::class, 'upload'])->name('upload');
        Route::get('/{filename}/download', [ProfileController::class, 'download'])->name('download');
        Route::get('/{filename}/{size}/download', [ProfileController::class, 'download'])->name('thumbnail');
    }
    );

Route::middleware(['web', 'auth', Blocs\Middleware\Role::class])
    ->prefix('admin/user')
    ->name('admin.user.')
    ->group(function () {
        Route::get('/', [UserController::class, 'index'])->name('index');
        Route::post('/search', [UserController::class, 'search'])->name('search');
        Route::get('/create', [UserController::class, 'create'])->name('create');
        Route::post('/', [UserController::class, 'store'])->name('store');
        Route::get('/{id}/edit', [UserController::class, 'edit'])->where('id', '[0-9]+')->name('edit');
        Route::post('/{id}', [UserController::class, 'update'])->where('id', '[0-9]+')->name('update');
        Route::get('/{id}/show', [UserController::class, 'show'])->where('id', '[0-9]+')->name('show');
        Route::post('/{id}/destroy', [UserController::class, 'destroy'])->where('id', '[0-9]+')->name('destroy');
        Route::post('/select', [UserController::class, 'select'])->name('select');
        Route::post('/{id}/toggle', [UserController::class, 'toggle'])->where('id', '[0-9]+')->name('toggle');
    }
    );

/*
Route::get('/route', function () {
    $routes = collect(Route::getRoutes())->map(function ($route) {
        return [
            'url' => $route->uri,
            'method' => $route->methods[0],
            'controller' => $route->action['controller'] ?? null,
            'name' => $route->action['as'] ?? null,
            'middlewares' => $route->action['middleware'] ?? [],
        ];
    });

    return response()->view('route', ['routes' => $routes])->header('Content-Type', 'text/xml');
});

Route::get('/validate', function () {
    $viewsDir = storage_path('framework/views/');

    $validates = [];
    $fileList = scandir($viewsDir);
    foreach ($fileList as $file) {
        if ('.' == substr($file, 0, 1) || '.json' != substr($file, -5)) {
            continue;
        }

        $jsonData = json_decode(file_get_contents(storage_path('framework/views/'.$file)));
        if (empty($jsonData->validate)) {
            continue;
        }

        foreach ($jsonData->validate as $template => $validate) {
            $labels = [];
            if (isset($jsonData->label->$template)) {
                foreach ($jsonData->label->$template as $formName => $label) {
                    if (false === strpos($label, 'data-')) {
                        $labels[$formName] = $label;
                    } else {
                        isset($blocsCompiler) || $blocsCompiler = new \Blocs\Compiler\BlocsCompiler();
                        $labels[$formName] = $blocsCompiler->render($label);
                    }
                }
            }

            foreach ($validate as $formName => $formValidates) {
                foreach ($formValidates as $formValidate) {
                    $templateLoc = str_replace(resource_path('views/'), '', $template);
                    list($messageKey) = explode(':', $formValidate, 2);
                    $validates[] = [
                        'loc' => $templateLoc,
                        'name' => $labels[$formName] ?? $formName,
                        'validate' => $formValidate,
                        'message' => isset($jsonData->message->$template->$formName->$messageKey) ? $jsonData->message->$template->$formName->$messageKey : '',
                    ];
                }
            }
        }

        foreach ($jsonData->upload as $formName => $uploadValidate) {
            foreach ($uploadValidate->validate as $formValidate) {
                list($messageKey) = explode(':', $formValidate, 2);
                $validates[] = [
                    'loc' => $templateLoc,
                    'name' => $formName,
                    'validate' => $formValidate,
                    'message' => isset($uploadValidate->message->$messageKey) ? $uploadValidate->message->$messageKey : '',
                ];
            }
        }
    }

    return response()->view('validate', ['validates' => $validates])->header('Content-Type', 'text/xml');
});

Route::get('/database', function () {
    $databases = [];
    $tables = \Schema::getTableListing();
    foreach ($tables as $table) {
        $columns = \Schema::getColumnListing($table);
        foreach ($columns as $column) {
            $databases[] = [
                'name' => $table,
                'column' => $column,
                'type' => \Schema::getColumnType($table, $column),
            ];
        }
    }

    return response()->view('database', ['databases' => $databases])->header('Content-Type', 'text/xml');
});
*/
