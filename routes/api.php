<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\AdminMenuController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\ProductCategoriesController;
use App\Http\Controllers\MasterBankController;
use App\Http\Controllers\MasterProductController;
use App\Http\Controllers\PublicController;
use App\Http\Controllers\MasterChartAccountController;
use App\Http\Controllers\ShipNoteCategoryController;
use App\Http\Controllers\MasterShipController;
use App\Http\Controllers\MasterShipTypeController;
use App\Http\Controllers\PurchaseOrderController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\StockInOutController;
use App\Http\Controllers\StockAdjustmentController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ExpensesController;
use App\Http\Controllers\ReportPurchaseController;
use App\Http\Controllers\ReportTransactionController;
use App\Http\Controllers\ReportExpensesController;
use App\Http\Controllers\ReportIncomeController;
use App\Http\Controllers\ConfigTaxController;
use App\Http\Controllers\PettyCashController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\JournalController;

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

    Route::group(['prefix' => 'supplier'], function () {
        Route::get('', [SupplierController::class, 'index']);
        Route::post('/create', [SupplierController::class, 'create']);
        Route::get('/detail/{id}', [SupplierController::class, 'detail']);
        Route::post('/update/{id}', [SupplierController::class, 'update']);
        Route::delete('/delete/{id}', [SupplierController::class, 'delete']);
        Route::get('/export_excel', [SupplierController::class, 'export_excel']);
        Route::get('/export_pdf', [SupplierController::class, 'export_pdf']);
    });
    
    Route::group(['prefix' => 'customer'], function () {
        Route::get('', [CustomerController::class, 'index']);
        Route::post('/create', [CustomerController::class, 'create']);
        Route::get('/detail/{id}', [CustomerController::class, 'detail']);
        Route::post('/update/{id}', [CustomerController::class, 'update']);
        Route::delete('/delete/{id}', [CustomerController::class, 'delete']);
        Route::get('/export_excel', [CustomerController::class, 'export_excel']);
        Route::get('/export_pdf', [CustomerController::class, 'export_pdf']);
    });
    
    Route::group(['prefix' => 'product/category'], function () {
        Route::get('', [ProductCategoriesController::class, 'index']);
        Route::post('/create', [ProductCategoriesController::class, 'create']);
        Route::get('/detail/{id}', [ProductCategoriesController::class, 'detail']);
        Route::patch('/update/{id}', [ProductCategoriesController::class, 'update']);
        Route::delete('/delete/{id}', [ProductCategoriesController::class, 'delete']);
    });
    
    Route::group(['prefix' => 'bank'], function () {
        Route::get('', [MasterBankController::class, 'index']);
        Route::post('/create', [MasterBankController::class, 'create']);
        Route::get('/detail/{id}', [MasterBankController::class, 'detail']);
        Route::patch('/update/{id}', [MasterBankController::class, 'update']);
        Route::patch('/additional_balance', [MasterBankController::class, 'additional_balance']);
        Route::delete('/delete/{id}', [MasterBankController::class, 'delete']);
    });
   
    Route::group(['prefix' => 'petty_cash'], function () {
        Route::get('', [PettyCashController::class, 'index']);
        Route::post('/create', [PettyCashController::class, 'create']);
        Route::get('/detail/{id}', [PettyCashController::class, 'detail']);
        Route::patch('/update/{id}', [PettyCashController::class, 'update']);
        Route::delete('/delete/{id}', [PettyCashController::class, 'delete']);
    });
    
    Route::group(['prefix' => 'master/product'], function () {
        Route::get('', [MasterProductController::class, 'index']);
        Route::get('/category', [MasterProductController::class, 'get_category']);
        Route::post('/create', [MasterProductController::class, 'create']);
        Route::get('/detail/{id}', [MasterProductController::class, 'detail']);
        Route::get('/fifo', [MasterProductController::class, 'get_fifo']);
        Route::post('/update/{id}', [MasterProductController::class, 'update']);
        Route::delete('/delete/{id}', [MasterProductController::class, 'delete']);
        Route::get('/export_excel', [MasterProductController::class, 'export_excel']);
        Route::get('/export_pdf', [MasterProductController::class, 'export_pdf']);
    });
  
    Route::group(['prefix' => 'stock'], function () {
        Route::get('', [StockInOutController::class, 'index']);
        Route::get('/export_excel', [StockInOutController::class, 'export_excel']);
        Route::get('/export_pdf', [StockInOutController::class, 'export_pdf']);
    });

    Route::group(['prefix' => 'master/coa'], function () {
        Route::get('', [MasterChartAccountController::class, 'index']);
        Route::post('/create', [MasterChartAccountController::class, 'create']);
        Route::get('/detail/{id}', [MasterChartAccountController::class, 'detail']);
        Route::patch('/update/{id}', [MasterChartAccountController::class, 'update']);
        Route::delete('/delete/{id}', [MasterChartAccountController::class, 'delete']);
    });

    Route::group(['prefix' => 'master/shipnotecategory'], function () {
        Route::get('', [ShipNoteCategoryController::class, 'index']);
        Route::post('/create', [ShipNoteCategoryController::class, 'create']);
        Route::get('/detail/{id}', [ShipNoteCategoryController::class, 'detail']);
        Route::patch('/update/{id}', [ShipNoteCategoryController::class, 'update']);
        Route::delete('/delete/{id}', [ShipNoteCategoryController::class, 'delete']);
    });

    Route::group(['prefix' => 'master/ship'], function () {
        Route::get('', [MasterShipController::class, 'index']);
        Route::post('/create', [MasterShipController::class, 'create']);
        Route::get('/detail/{id}', [MasterShipController::class, 'detail']);
        Route::post('/update/{id}', [MasterShipController::class, 'update']);
        Route::delete('/delete/{id}', [MasterShipController::class, 'delete']);
    });

    Route::group(['prefix' => 'master/ship/type'], function () {
        Route::get('', [MasterShipTypeController::class, 'index']);
        Route::post('/create', [MasterShipTypeController::class, 'create']);
        Route::get('/detail/{id}', [MasterShipTypeController::class, 'detail']);
        Route::patch('/update/{id}', [MasterShipTypeController::class, 'update']);
        Route::delete('/delete/{id}', [MasterShipTypeController::class, 'delete']);
    });
   
    Route::group(['prefix' => 'purchase_order'], function () {
        Route::get('', [PurchaseOrderController::class, 'index']);
        Route::post('/create', [PurchaseOrderController::class, 'create']);
        Route::get('/detail/{id}', [PurchaseOrderController::class, 'detail']);
        Route::patch('/update/{id}', [PurchaseOrderController::class, 'update']);
        Route::patch('/update_status/{id}', [PurchaseOrderController::class, 'updateStatus']);
        Route::patch('/update_receiving/{id}', [PurchaseOrderController::class, 'updateReceiving']);
        Route::patch('/update_payment/{id}', [PurchaseOrderController::class, 'updatePayment']);
        Route::get('/export_excel', [PurchaseOrderController::class, 'export_excel']);
        Route::get('/export_pdf', [PurchaseOrderController::class, 'export_pdf']);
        Route::get('/invoice/{id}', [PurchaseOrderController::class, 'invoice']);
    });
   
    Route::group(['prefix' => 'transaction'], function () {
        Route::get('', [TransactionController::class, 'index']);
        Route::post('/create', [TransactionController::class, 'create']);
        Route::patch('/update/{id}', [TransactionController::class, 'update']);
        Route::get('/detail/{id}', [TransactionController::class, 'detail']);
        Route::patch('/update_status/{id}', [TransactionController::class, 'updateStatus']);
        Route::patch('/print/travel_doc/{id}', [TransactionController::class, 'printTravelDoc']);
        Route::patch('/update_delivery/{id}', [TransactionController::class, 'updateDelivery']);
        Route::patch('/update_payment/{id}', [TransactionController::class, 'updatePayment']);
        Route::get('/export_excel', [TransactionController::class, 'export_excel']);
        Route::get('/export_pdf', [TransactionController::class, 'export_pdf']);
        Route::get('/invoice/{id}', [TransactionController::class, 'invoice']);
        Route::get('/delivery_letter/{id}', [TransactionController::class, 'delivery_letter']);
    });
    
    Route::group(['prefix' => 'stock_adjustment'], function () {
        Route::get('', [StockAdjustmentController::class, 'index']);
        Route::post('/create', [StockAdjustmentController::class, 'create']);
        Route::get('/detail/{id}', [StockAdjustmentController::class, 'detail']);
        Route::get('/export_excel', [StockAdjustmentController::class, 'export_excel']);
        Route::get('/export_pdf', [StockAdjustmentController::class, 'export_pdf']);
    });

    Route::group(['prefix' => 'dashboard'], function () {
        Route::get('/widget', [DashboardController::class, 'widget']);
        Route::get('/stock_warning', [DashboardController::class, 'stock_warning']);
        Route::get('/chart', [DashboardController::class, 'chart']);
        Route::get('/user_log', [DashboardController::class, 'user_log']);
    });
    
    Route::group(['prefix' => 'expenses'], function () {
        Route::get('', [ExpensesController::class, 'index']);
        Route::get('/summary', [ExpensesController::class, 'summary']);
        Route::get('/chart_account', [ExpensesController::class, 'get_chart_account']);
        Route::post('/create', [ExpensesController::class, 'create']);
        Route::get('/detail/{id}', [ExpensesController::class, 'detail']);
        Route::post('/update/{id}', [ExpensesController::class, 'update']);
        Route::patch('/update/status/{id}', [ExpensesController::class, 'updateStatus']);
        Route::delete('/delete/{id}', [ExpensesController::class, 'delete']);
        Route::get('export_excel', [ExpensesController::class, 'export_excel']);
        Route::get('/export_pdf', [ExpensesController::class, 'export_pdf']);
    });

    Route::group(['prefix' => 'report/purchase'], function () {
        Route::get('', [ReportPurchaseController::class, 'index']);
        Route::get('/daily', [ReportPurchaseController::class, 'daily']);
        Route::get('/export_excel', [ReportPurchaseController::class, 'export_excel']);
        Route::get('/export_pdf', [ReportPurchaseController::class, 'export_pdf']);
    });

    Route::group(['prefix' => 'report/sales'], function () {
        Route::get('', [ReportTransactionController::class, 'index']);
        Route::get('/daily', [ReportTransactionController::class, 'daily']);
        Route::get('/export_excel', [ReportTransactionController::class, 'export_excel']);
        Route::get('/export_pdf', [ReportTransactionController::class, 'export_pdf']);
    });
   
    Route::group(['prefix' => 'report/expenses'], function () {
        Route::get('', [ReportExpensesController::class, 'index']);
        Route::get('/daily', [ReportExpensesController::class, 'daily']);
    });

    Route::group(['prefix' => 'report/income'], function () {
        Route::get('', [ReportIncomeController::class, 'index']);
        Route::get('/export_excel', [ReportIncomeController::class, 'export_excel']);
        Route::get('/export_pdf', [ReportIncomeController::class, 'export_pdf']);
    });

    Route::group(['prefix' => 'config/tax'], function () {
        Route::get('', [ConfigTaxController::class, 'index']);
        Route::post('/create', [ConfigTaxController::class, 'create']);
        Route::patch('/update/{id}', [ConfigTaxController::class, 'update']);
    });
   
    Route::group(['prefix' => 'journal'], function () {
        Route::get('', [JournalController::class, 'index']);
        Route::get('/export_excel', [JournalController::class, 'export_excel']);
        Route::get('/export_pdf', [JournalController::class, 'export_pdf']);
    });
    
    Route::group(['prefix' => 'profile'], function () {
        Route::get('', [ProfileController::class, 'index']);
        Route::patch('/update/{id}', [ProfileController::class, 'update']);
    });

});



