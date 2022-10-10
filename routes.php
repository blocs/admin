// Routing for blocs/admin

Auth::routes();

use App\Http\Controllers\Admin\HomeController;
Route::get('/home', [HomeController::class, 'index'])->name('home');
Route::get('/user', [HomeController::class, 'index'])->name('user');
Route::get('/dashboard/user', [HomeController::class, 'dashboard']);
