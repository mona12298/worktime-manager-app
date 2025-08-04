<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\TimeClockController;
use Laravel\Fortify\Http\Controllers\AuthenticatedSessionController;
use App\Http\Controllers\AdminAttendanceExportController;


/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/login', fn () => view('auth.user_login'))->name('login');
Route::post('/login', [AuthenticatedSessionController::class, 'store']);

Route::get('/admin/login',[AuthController::class, 'showAdminLoginForm']);
Route::post('/admin/login', [AuthController::class, 'loginAdmin']);

Route::get('/attendance/list', [UserController::class, 'indexUserAttendance']);

Route::middleware(['auth:web'])->group(function () {
    Route::get('/attendance', [TimeClockController::class, 'showClockInForm']);
    Route::post('/attendance', [TimeClockController::class, 'storeAttendance']);

    Route::post('/attendance/{id}', [UserController::class, 'storeUserCorrectionRequest']);
});

Route::middleware(['auth:admin', 'admin'])->group(function () {
    Route::get('/admin/attendance/list', [AdminController::class, 'indexAdminAttendance']);
    Route::get('/admin/staff/list', [AdminController::class, 'indexStaff']);
    Route::get('/admin/attendance/staff/{id}', [AdminController::class, 'indexAttendanceByStaff']);
    Route::get('/admin/attendance/staff/export/{user}', [AdminAttendanceExportController::class, 'exportCsv']);
    Route::get('/stamp_correction_request/approve/{attendance_correct_request}', [AdminController::class, 'showStampCorrectionRequest']);
});

Route::get('/attendance/{id}', function ($id) {
    if (auth('admin')->check()) {
        return app(AdminController::class)->showAdminAttendance($id);
    } elseif (auth('web')->check()) {
        return app(UserController::class)->showUserAttendance($id);
    } else {
        abort(403, 'アクセス権限がありません');
    }
})->middleware('auth');

Route::get('/stamp_correction_request/list', function () {
    if (auth('admin')->check()) {
        return app(AdminController::class)->indexAdminStampRequests();
    }

    if (auth('web')->check()) {
        return app(UserController::class)->indexUserStampRequests();
    }
})->middleware('auth');