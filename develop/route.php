use App\Http\Controllers\CONTROLLER_NAME;

Route::middleware(['auth', Blocs\Middleware\Role::class])
    ->prefix('ROUTE_PREFIX')
    ->name('ROUTE_NAME')
    ->group(function () {
        Route::get('/', [CONTROLLER_NAME::class, 'index'])->name('index');
        Route::post('/search', [CONTROLLER_NAME::class, 'search'])->name('search');
        Route::get('/create', [CONTROLLER_NAME::class, 'create'])->name('create');
        Route::post('/', [CONTROLLER_NAME::class, 'store'])->name('store');
        Route::get('/{id}/edit', [CONTROLLER_NAME::class, 'edit'])->where('id', '[0-9]+')->name('edit');
        Route::post('/{id}', [CONTROLLER_NAME::class, 'update'])->where('id', '[0-9]+')->name('update');
        Route::post('/{id}/destroy', [CONTROLLER_NAME::class, 'destroy'])->where('id', '[0-9]+')->name('destroy');
        Route::post('/select', [CONTROLLER_NAME::class, 'select'])->name('select');
        Route::post('/{id}/toggle', [CONTROLLER_NAME::class, 'toggle'])->where('id', '[0-9]+')->name('toggle');
        Route::post('/upload', [CONTROLLER_NAME::class, 'upload'])->name('upload');
        Route::get('/{filename}/download', [CONTROLLER_NAME::class, 'download'])->name('download');
        Route::get('/{filename}/{size}/download', [CONTROLLER_NAME::class, 'download'])->name('thumbnail');
    }
    );
