// Routing for blocs/admin

Auth::routes();

use App\Http\Middleware\Admin\UserGroup;
use App\Http\Controllers\Admin\HomeController;
use App\Http\Controllers\Admin\ProfileController;
use App\Http\Controllers\Admin\UserController;

Route::middleware(['auth', UserGroup::class])
	->group(function () {
		Route::get('/home', [HomeController::class, 'index'])->name('home');
		Route::get('/clear', [HomeController::class, 'clear']);
	}
);

Route::middleware(['auth', UserGroup::class])
	->prefix('profile')
	->name('profile.')
	->group(function () {
		Route::get('/{id}/edit', [ProfileController::class, 'edit'])->where('id', '[0-9]+')->name('edit');
		Route::post('/{id}', [ProfileController::class, 'update'])->where('id', '[0-9]+')->name('update');
		Route::post('/upload', [ProfileController::class, 'upload'])->name('upload');
		Route::get('/{filename}/download', [ProfileController::class, 'download'])->name('download');
		Route::get('/{filename}/{size}/download', [ProfileController::class, 'download'])->name('thumbnail');
	}
);

Route::middleware(['guest', UserGroup::class])
	->prefix('user')
	->name('user.')
	->group(function () {
		Route::get('/', [UserController::class, 'index'])->name('index');
		Route::post('/search', [UserController::class, 'index'])->name('search');
		Route::get('/create', [UserController::class, 'create'])->name('create');
		Route::post('/', [UserController::class, 'store'])->name('store');
		Route::get('/{id}/edit', [UserController::class, 'edit'])->where('id', '[0-9]+')->name('edit');
		Route::post('/{id}', [UserController::class, 'update'])->where('id', '[0-9]+')->name('update');
		Route::post('/{id}/destroy', [UserController::class, 'destroy'])->where('id', '[0-9]+')->name('destroy');
		Route::post('/select', [UserController::class, 'select'])->name('select');
		Route::post('/{id}/toggle', [UserController::class, 'toggle'])->where('id', '[0-9]+')->name('toggle');
	}
);
