// Routing for blocs/admin

Auth::routes();

use App\Http\Middleware\Admin\UserGroup;
use App\Http\Controllers\Admin\HomeController;
use App\Http\Controllers\Admin\ProfileController;
use App\Http\Controllers\Admin\UserController;

Route::middleware(['auth', UserGroup::class])
	->group(function () {
		Route::get('/home', [HomeController::class, 'index'])->name('home');
		Route::get('/dashboard', [HomeController::class, 'dashboard'])->name('dashboard');
		Route::get('/clear', [HomeController::class, 'clear']);
	}
);

Route::middleware(['auth', UserGroup::class])
	->prefix('profile')
	->name('profile.')
	->group(function () {
		Route::get('/entry/{id?}', [ProfileController::class, 'entry'])->name('entry');
		Route::post('/update/{id}', [ProfileController::class, 'update'])->name('update');
	}
);

Route::middleware(['guest', UserGroup::class])
	->prefix('user')
	->name('user.')
	->group(function () {
		Route::get('/', [UserController::class, 'index'])->name('index');
		Route::post('/', [UserController::class, 'index'])->name('search');
		Route::get('/entry/{id?}', [UserController::class, 'entry'])->name('entry');
		Route::post('/entry/{id?}', [UserController::class, 'entry'])->name('back');
		Route::post('/confirm_insert', [UserController::class, 'confirm_insert'])->name('confirm_insert');
		Route::post('/insert', [UserController::class, 'insert'])->name('insert');
		Route::post('/confirm_update/{id}', [UserController::class, 'confirm_update'])->name('confirm_update');
		Route::post('/update/{id}', [UserController::class, 'update'])->name('update');
		Route::post('/confirm_delete/{id}', [UserController::class, 'confirm_delete'])->name('confirm_delete');
		Route::post('/delete/{id}', [UserController::class, 'delete'])->name('delete');
		Route::post('/confirm_select', [UserController::class, 'confirm_select'])->name('confirm_select');
		Route::post('/select', [UserController::class, 'select'])->name('select');
		Route::post('/toggle/{id}', [UserController::class, 'toggle'])->name('toggle');
	}
);
