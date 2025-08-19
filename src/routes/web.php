<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\TimeClockController;
use Laravel\Fortify\Http\Controllers\AuthenticatedSessionController;
use App\Http\Controllers\AdminAttendanceExportController;
use App\Http\Requests\CorrectionFormRequest;


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
Route::post('/logout', [AuthController::class, 'logout']);
Route::get('/admin/login',[AuthController::class, 'showAdminLoginForm']);
Route::post('/admin/login', [AuthController::class, 'loginAdmin']);

Route::middleware(['auth:web'])->group(function () {
    Route::get('/attendance', [TimeClockController::class, 'showClockInForm']);
    Route::post('/attendance', [TimeClockController::class, 'storeAttendance']);
    Route::get('/attendance/list', [UserController::class, 'indexUserAttendance']);
});

Route::prefix('admin')
    ->middleware('auth:admin')
    ->group(function () {
    Route::get('/attendance/list', [AdminController::class, 'indexAdminAttendance']);
    Route::get('/staff/list', [AdminController::class, 'indexStaff']);
    Route::get('/attendance/staff/{id}', [AdminController::class, 'indexAttendanceByStaff']);
    Route::get('/attendance/staff/export/{user}', [AdminAttendanceExportController::class, 'exportCsv']);
});

Route::prefix('stamp_correction_request/approve')->middleware('auth:admin')->group(function () {
    Route::get('/{attendance_correct_request}', [AdminController::class, 'showStampCorrectionRequest']);
    Route::post('/{attendance_correct_request}', [AdminController::class, 'approveStampCorrectionRequest']);
});


Route::post('/attendance/{id}', function ($id) {
    $request = CorrectionFormRequest::capture();

    if (auth('admin')->check()) {
        return app(\App\Http\Controllers\AdminController::class)
            ->storeAdminCorrectionRequest($request, $id);
    } elseif (auth('web')->check()) {
        return app(\App\Http\Controllers\UserController::class)
            ->storeUserCorrectionRequest($request, $id);
    } else {
        abort(403, 'アクセス権限がありません');
    }
});

Route::get('/attendance/{id}', function ($id) {
    if (auth('admin')->check()) {
        return app(AdminController::class)->showAdminAttendance($id);
    } elseif (auth('web')->check()) {
        return app(UserController::class)->showUserAttendance($id);
    } else {
        abort(403, 'アクセス権限がありません');
    }
});



Route::get('/stamp_correction_request/list', function () {
    if (!auth('admin')->check() && !auth('web')->check()) {
        abort(403, 'アクセス権限がありません');
    }
    if (auth('admin')->check()) {
        return app(AdminController::class)->indexAdminStampRequests();
    } else {
        return app(UserController::class)->indexUserStampRequests();
    }
});