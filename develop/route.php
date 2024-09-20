use App\Http\ControllersCONTROLLER_DIRNAME\CONTROLLER_BASENAME;

Route::middleware(['auth', Blocs\Middleware\Role::class])
    ->prefix('ROUTE_PREFIX')
    ->name('ROUTE_NAME')
    ->group(function () {
        Route::get('/', [CONTROLLER_BASENAME::class, 'index'])->name('index');
        Route::post('/search', [CONTROLLER_BASENAME::class, 'search'])->name('search');
        Route::get('/create', [CONTROLLER_BASENAME::class, 'create'])->name('create');
        Route::post('/', [CONTROLLER_BASENAME::class, 'store'])->name('store');
        Route::get('/{id}/edit', [CONTROLLER_BASENAME::class, 'edit'])->where('id', '[0-9]+')->name('edit');
        Route::post('/{id}', [CONTROLLER_BASENAME::class, 'update'])->where('id', '[0-9]+')->name('update');
        Route::get('/{id}/show', [CONTROLLER_BASENAME::class, 'show'])->where('id', '[0-9]+')->name('show');
        Route::post('/{id}/destroy', [CONTROLLER_BASENAME::class, 'destroy'])->where('id', '[0-9]+')->name('destroy');
        Route::post('/select', [CONTROLLER_BASENAME::class, 'select'])->name('select');
        Route::post('/{id}/toggle', [CONTROLLER_BASENAME::class, 'toggle'])->where('id', '[0-9]+')->name('toggle');
        Route::post('/upload', [CONTROLLER_BASENAME::class, 'upload'])->name('upload');
        Route::get('/{filename}/download', [CONTROLLER_BASENAME::class, 'download'])->name('download');
        Route::get('/{filename}/{size}/download', [CONTROLLER_BASENAME::class, 'download'])->name('thumbnail');
    }
    );
