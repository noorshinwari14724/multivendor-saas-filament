<?php

use Illuminate\Support\Facades\Route;
use \App\Http\Controllers\VendorLoginController;
/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return redirect('/admin');
});

Route::get('/login', function () {
    return redirect('/admin/login');
})->name('login');

Route::get('/admin/vendor/login-as/{vendor}', [VendorLoginController::class, 'loginAs'])
    ->name('admin.vendor.login-as');

Route::middleware(['auth'])->group(function () {
    // Add your authenticated routes here
});

require __DIR__.'/auth.php';
