// Routing for blocs/admin

Auth::routes();

use App\Http\Controllers\Admin\HomeController;
Route::middleware('auth')
	->group(function () {
		Route::get('/home', [HomeController::class, 'index'])->name('home');
		Route::get('/dashboard', [HomeController::class, 'dashboard']);
		Route::get('/clear', [HomeController::class, 'clear']);
	}
);

use App\Http\Controllers\Admin\ProfileController;
Route::middleware('auth')
	->prefix('profile')
	->name('profile.')
	->group(function () {
		Route::get('/entry', [ProfileController::class, 'entry'])->name('entry');
		Route::post('/update', [ProfileController::class, 'update'])->name('update');
	}
);

use App\Http\Controllers\Admin\UserController;
Route::middleware('auth')
	->prefix('user')
	->name('user.')
	->group(function () {
		Route::get('/list', [UserController::class, 'list'])->name('list');
		Route::post('/list', [UserController::class, 'list'])->name('plist');
		Route::get('/entry/{id?}', [UserController::class, 'entry'])->name('entry');
		Route::post('/insert', [UserController::class, 'insert'])->name('insert');
		Route::post('/update/{id?}', [UserController::class, 'update'])->name('update');
	}
);
