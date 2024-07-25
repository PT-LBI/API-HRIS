<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\AdminMenuController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\PublicController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;

Route::group(['middleware' => 'api'], function () {
    Route::get('check/health', [PublicController::class, 'checkHealth']);
    Route::get('district/{id}', [PublicController::class, 'district']);
    Route::get('province', [PublicController::class, 'province']);
    Route::get('pdf', [ReportIncomeController::class, 'export_pdf']);
    Route::post('user/create', [UserController::class, 'create']);
});

Route::group(['middleware' => 'api', 'prefix' => 'auth'], function ($router) {
    Route::post('login', [AuthController::class, 'login']);
    Route::post('login/mobile', [AuthController::class, 'login_mobile']);
    Route::post('logout', [AuthController::class, 'logout']);
});

Route::get('/test', function () {
    return JWTAuth::parseToken()->authenticate();
 });

// Route::group(['middleware' => 'api', 'prefix' => 'user'], function () {
Route::middleware('auth:api')->group(function() {

    Route::group(['prefix' => 'user'], function () {
        Route::get('', [UserController::class, 'index']);
        // Route::post('/create', [UserController::class, 'create']);
        Route::get('/detail/{id}', [UserController::class, 'detail']);
        Route::post('/update/{id}', [UserController::class, 'update']);
        Route::delete('/delete/{id}', [UserController::class, 'delete']);
        Route::get('/export_excel', [UserController::class, 'export_excel']);
        Route::get('/export_pdf', [UserController::class, 'export_pdf']);
    });

    Route::group(['prefix' => 'menu'], function () {
        Route::get('', [AdminMenuController::class, 'index']);
        Route::post('/create', [AdminMenuController::class, 'create']);
        Route::get('/detail/{id}', [AdminMenuController::class, 'detail']);
        Route::patch('/update/{id}', [AdminMenuController::class, 'update']);
        Route::delete('/delete/{id}', [AdminMenuController::class, 'delete']);
    });

    Route::group(['prefix' => 'employee'], function () {
        Route::get('', [EmployeeController::class, 'index']);
        Route::post('/create', [EmployeeController::class, 'create']);
        Route::get('/detail/{id}', [EmployeeController::class, 'detail']);
        Route::post('/update/{id}', [EmployeeController::class, 'update']);
        Route::delete('/delete/{id}', [EmployeeController::class, 'delete']);
        // Route::get('/export_excel', [EmployeeController::class, 'export_excel']);
        // Route::get('/export_pdf', [EmployeeController::class, 'export_pdf']);
    });
    
    Route::group(['prefix' => 'dashboard'], function () {
        Route::get('/widget', [DashboardController::class, 'widget']);
        Route::get('/stock_warning', [DashboardController::class, 'stock_warning']);
        Route::get('/chart', [DashboardController::class, 'chart']);
        Route::get('/user_log', [DashboardController::class, 'user_log']);
    });
    
    Route::group(['prefix' => 'profile'], function () {
        Route::get('', [ProfileController::class, 'index']);
        Route::patch('/update/{id}', [ProfileController::class, 'update']);
    });

});



