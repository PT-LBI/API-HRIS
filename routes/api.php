<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\PublicController;
use App\Http\Controllers\Superadmin\UserController;
use App\Http\Controllers\Superadmin\AdminMenuController;
use App\Http\Controllers\Superadmin\EmployeeController;
use App\Http\Controllers\Superadmin\DashboardController;
use App\Http\Controllers\Superadmin\ProfileController;
use App\Http\Controllers\Superadmin\CompanyController;
use App\Http\Controllers\Superadmin\DivisionController;
use App\Http\Controllers\Superadmin\AnnouncementController;
use App\Http\Controllers\Superadmin\MasterPayrollController;
use App\Http\Controllers\Superadmin\UserPayrollController;
use App\Http\Controllers\Superadmin\ShiftController;
use App\Http\Controllers\Superadmin\ScheduleController;
use App\Http\Controllers\Superadmin\LeaveController;
use App\Http\Controllers\Superadmin\PresenceController;
use App\Http\Controllers\Superadmin\MasterLocationController;
use App\Http\Controllers\Mobile\MyProfileController;
use App\Http\Controllers\Mobile\MyAnnouncementController;
use App\Http\Controllers\Mobile\MyScheduleController;
use App\Http\Controllers\Mobile\MyLeaveController;
use App\Http\Controllers\Mobile\MyPresenceController;
use App\Http\Controllers\Mobile\MyNotifController;
use App\Http\Controllers\Mobile\MyDashboardController;

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

    Route::group(['prefix' => 'company'], function () {
        Route::get('', [CompanyController::class, 'index']);
        Route::post('/create', [CompanyController::class, 'create']);
        Route::get('/detail/{id}', [CompanyController::class, 'detail']);
        Route::patch('/update/{id}', [CompanyController::class, 'update']);
        Route::delete('/delete/{id}', [CompanyController::class, 'delete']);
    });

    Route::group(['prefix' => 'division'], function () {
        Route::get('', [DivisionController::class, 'index']);
        Route::post('/create', [DivisionController::class, 'create']);
        Route::get('/detail/{id}', [DivisionController::class, 'detail']);
        Route::patch('/update/{id}', [DivisionController::class, 'update']);
        Route::delete('/delete/{id}', [DivisionController::class, 'delete']);
    });

    Route::group(['prefix' => 'announcement'], function () {
        Route::get('', [AnnouncementController::class, 'index']);
        Route::post('/create', [AnnouncementController::class, 'create']);
        Route::get('/detail/{id}', [AnnouncementController::class, 'detail']);
        Route::post('/update/{id}', [AnnouncementController::class, 'update']);
        Route::delete('/delete/{id}', [AnnouncementController::class, 'delete']);
    });
    
    Route::group(['prefix' => 'master_payroll'], function () {
        Route::get('', [MasterPayrollController::class, 'index']);
        Route::post('/create', [MasterPayrollController::class, 'create']);
        Route::get('/detail/{id}', [MasterPayrollController::class, 'detail']);
        Route::get('/monthly_payroll', [MasterPayrollController::class, 'monthly_payroll']);
        Route::patch('/update/{id}', [MasterPayrollController::class, 'update']);
        Route::delete('/delete/{id}', [MasterPayrollController::class, 'delete']);
    });
    
    Route::group(['prefix' => 'user_payroll'], function () {
        // Route::get('', [UserPayrollController::class, 'index']);
        Route::post('/create', [UserPayrollController::class, 'create']);
        Route::get('/detail/{id}', [UserPayrollController::class, 'detail']);
        // Route::get('/monthly_payroll_detail/{id}', [UserPayrollController::class, 'monthly_payroll_detail']);
        Route::patch('/update/{id}', [UserPayrollController::class, 'update']);
        Route::patch('/send/{id}', [UserPayrollController::class, 'send']);
        // Route::delete('/delete/{id}', [UserPayrollController::class, 'delete']);
    });
    
    Route::group(['prefix' => 'shift'], function () {
        Route::get('', [ShiftController::class, 'index']);
        Route::post('/create', [ShiftController::class, 'create']);
        Route::get('/detail/{id}', [ShiftController::class, 'detail']);
        Route::patch('/update/{id}', [ShiftController::class, 'update']);
        Route::delete('/delete/{id}', [ShiftController::class, 'delete']);
    });
    
    Route::group(['prefix' => 'schedule'], function () {
        Route::get('', [ScheduleController::class, 'index']);
        Route::post('/create', [ScheduleController::class, 'create']);
        Route::post('/create_bulk', [ScheduleController::class, 'create_bulk']);
        Route::get('/detail/{id}', [ScheduleController::class, 'detail']);
        Route::patch('/update/{id}', [ScheduleController::class, 'update']);
        Route::delete('/delete/{id}', [ScheduleController::class, 'delete']);
    });

    Route::group(['prefix' => 'leave'], function () {
        Route::get('', [LeaveController::class, 'index']);
        Route::get('/detail/{id}', [LeaveController::class, 'detail']);
        Route::patch('/update/{id}', [LeaveController::class, 'update']);
    });
    
    Route::group(['prefix' => 'presence'], function () {
        Route::get('', [PresenceController::class, 'index']);
        Route::get('/detail/{id}', [PresenceController::class, 'detail']);
    });

    Route::group(['prefix' => 'master_location'], function () {
        Route::get('', [MasterLocationController::class, 'index']);
        Route::post('/create', [MasterLocationController::class, 'create']);
        Route::get('/detail/{id}', [MasterLocationController::class, 'detail']);
        Route::patch('/update/{id}', [MasterLocationController::class, 'update']);
        Route::delete('/delete/{id}', [MasterLocationController::class, 'delete']);
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


    //==========> MOBILE <==========

    Route::group(['prefix' => 'mobile/profile'], function () {
        Route::get('', [MyProfileController::class, 'index']);
        Route::patch('/update/{id}', [MyProfileController::class, 'update']);
    });

    Route::group(['prefix' => 'mobile/announcement'], function () {
        Route::get('', [MyAnnouncementController::class, 'index']);
        Route::get('/detail/{id}', [MyAnnouncementController::class, 'detail']);
    });
   
    Route::group(['prefix' => 'mobile/leave'], function () {
        Route::get('', [MyLeaveController::class, 'index']);
        Route::post('/create', [MyLeaveController::class, 'create']);
        Route::get('/detail/{id}', [MyLeaveController::class, 'detail']);
    });

    Route::group(['prefix' => 'mobile/schedule'], function () {
        Route::get('', [MyScheduleController::class, 'index']);
    });
  
    Route::group(['prefix' => 'mobile/presence'], function () {
        Route::get('', [MyPresenceController::class, 'index']);
        Route::post('/create', [MyPresenceController::class, 'create']);
    });

    Route::group(['prefix' => 'mobile/notif'], function () {
        Route::get('', [MyNotifController::class, 'index']);
    });
   
    Route::group(['prefix' => 'mobile/dashboard'], function () {
        Route::get('', [MyDashboardController::class, 'index']);
    });

});



