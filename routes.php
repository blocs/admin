// Routing for blocs/admin

Auth::routes();

use App\Http\Controllers\Admin\HomeController;
Route::get('/home', [HomeController::class, 'index'])->name('home');
Route::get('/dashboard/user', [HomeController::class, 'dashboard']);

use App\Http\Controllers\Admin\ProfileController;
Route::get('/profile', [ProfileController::class, 'entry'])->name('profile');
Route::post('/profile/submit', [ProfileController::class, 'submit']);

Route::get('/user', [HomeController::class, 'index'])->name('user');
