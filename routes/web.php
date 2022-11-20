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
		Route::get('/entry/{id?}', [ProfileController::class, 'entry'])->name('entry');
		Route::post('/update/{id}', [ProfileController::class, 'update'])->name('update');
		Route::get('/upload', [ProfileController::class, 'upload'])->name('upload_list');
		Route::post('/upload', [ProfileController::class, 'upload'])->name('upload');
		Route::get('/download/{filename}/{size?}', [ProfileController::class, 'download'])->name('download');
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
		Route::post('/insert', [UserController::class, 'insert'])->name('insert');
		Route::post('/update/{id}', [UserController::class, 'update'])->name('update');
		Route::post('/delete/{id}', [UserController::class, 'delete'])->name('delete');
		Route::post('/select', [UserController::class, 'select'])->name('select');
		Route::post('/toggle/{id}', [UserController::class, 'toggle'])->name('toggle');
	}
);
