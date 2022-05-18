<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\DailyFeeController;
use App\Http\Controllers\Api\V1\DamageItemController;
use App\Http\Controllers\Api\V1\ExpenseController;
use App\Http\Controllers\Api\V1\ExpenseNameController;
use App\Http\Controllers\Api\V1\InvoiceController;
use App\Http\Controllers\Api\V1\ItemController;
use App\Http\Controllers\Api\V1\ItemTransferController;
use App\Http\Controllers\Api\V1\MemberController;
use App\Http\Controllers\Api\V1\MerchantController;
use App\Http\Controllers\Api\V1\OwnerUsedItemController;
use App\Http\Controllers\Api\V1\PurchaseController;
use App\Http\Controllers\Api\V1\PurchaseCreditController;
use App\Http\Controllers\Api\V1\ServiceController;
use App\Http\Controllers\Api\V1\ShopController;
use App\Http\Controllers\Api\V1\StaffController;
use App\Http\Controllers\Api\V1\StockController;
use App\Http\Controllers\Api\V1\ReportController;

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Route;

Route::namespace('Api\V1')->group(function () {
    Route::prefix('v1')->group(function () {

        Route::post('io-register', [AuthController::class, 'register']);
        Route::post('io-login', [AuthController::class, 'login']);

        //stocks
        Route::get('stocks', [StockController::class, 'index']);
        Route::post('stocks', [StockController::class, 'store']);
        Route::get('stocks/{stock}', [StockController::class, 'show']);
        Route::put('stocks/{stock}', [StockController::class, 'update']);
        Route::delete('stocks/{stock}', [StockController::class, 'destroy']);

        //purchase
        // Purchases
        Route::get('purchases', [PurchaseController::class, 'index']);
        Route::get('purchaseReport', [PurchaseController::class, 'reportPurchase']);
        Route::get('purchases/{purchase}', [PurchaseController::class, 'show']);
        Route::post('purchases', [PurchaseController::class, 'store']);
        Route::put('purchases/{purchase}', [PurchaseController::class, 'update']);
        Route::delete('purchases/{purchase}', [PurchaseController::class, 'destroy']);
        Route::delete('purchase_items/{purchase_item}', [PurchaseController::class, 'deletePurchaseItem']);

        
        Route::middleware(['auth:api'])->group(function () {
            // Users
            Route::get('users', [AuthController::class, 'index']);
            Route::get('user', [AuthController::class, 'user']);
            Route::put('user', [AuthController::class, 'update']);
            Route::get('logout', [AuthController::class, 'logout']);
            Route::post('io-change-password', [AuthController::class, 'changePassword']);
            Route::delete('users/{user}', [AuthController::class, 'destroy']);

            // Shops
            Route::get('shops', [ShopController::class, 'index']);
            Route::post('shops', [ShopController::class, 'store']);
            Route::get('shops/{shop}', [ShopController::class, 'show']);
            Route::put('shops/{shop}', [ShopController::class, 'update']);
            Route::delete('shops/{shop}', [ShopController::class, 'destroy']);

            // Expenses
            Route::get('expenses', [ExpenseController::class, 'index']);
            Route::post('expenses', [ExpenseController::class, 'store']);
            Route::post('expenses/batchInsert', [ExpenseController::class, 'batchInsert']);
            Route::get('expenses/{expense}', [ExpenseController::class, 'show']);
            Route::put('expenses/{expense}', [ExpenseController::class, 'update']);
            Route::delete('expenses/{expense}', [ExpenseController::class, 'destroy']);

            // Expense Names
            Route::get('expense-names', [ExpenseNameController::class, 'index']);
            Route::post('expense-names', [ExpenseNameController::class, 'store']);
            Route::get('expense-names/{expense_name}', [ExpenseNameController::class, 'show']);
            Route::put('expense-names/{expense_name}', [ExpenseNameController::class, 'update']);
            Route::delete('expense-names/{expense_name}', [ExpenseNameController::class, 'destroy']);

            // Members
            Route::get('members', [MemberController::class, 'index']);
            Route::post('members', [MemberController::class, 'store']);
            Route::get('members/{member}', [MemberController::class, 'show']);
            Route::put('members/{member}', [MemberController::class, 'update']);
            Route::delete('members/{member}', [MemberController::class, 'destroy']);

            // Merchants
            Route::get('merchants', [MerchantController::class, 'index']);
            Route::post('merchants', [MerchantController::class, 'store']);
            Route::get('merchants/{merchant}', [MerchantController::class, 'show']);
            Route::put('merchants/{merchant}', [MerchantController::class, 'update']);
            Route::delete('merchants/{merchant}', [MerchantController::class, 'destroy']);

            // Items
            Route::get('items', [ItemController::class, 'index']);
            Route::get('items/bestItem', [ItemController::class, 'bestItem']);
            Route::post('items', [ItemController::class, 'store']);
            Route::post('items/batchInsert', [ItemController::class, 'batchInsert']);
            Route::get('items/{item}', [ItemController::class, 'show']);
            Route::put('items/{item}', [ItemController::class, 'update']);
            Route::delete('items/{item}', [ItemController::class, 'destroy']);

            // Services
            Route::get('services', [ServiceController::class, 'index']);
            Route::get('services/bestService', [ServiceController::class, 'bestService']);
            Route::post('services', [ServiceController::class, 'store']);
            Route::post('services/batchInsert', [ServiceController::class, 'batchInsert']);
            Route::get('services/{service}', [ServiceController::class, 'show']);
            Route::put('services/{service}', [ServiceController::class, 'update']);
            Route::delete('services/{service}', [ServiceController::class, 'destroy']);

            // Staffs
            Route::get('staffs', [StaffController::class, 'index']);
            Route::get('staffReport', [StaffController::class, 'getStaffReport']);
            Route::post('staffs', [StaffController::class, 'store']);
            Route::get('staffs/{staff}', [StaffController::class, 'show']);
            Route::put('staffs/{staff}', [StaffController::class, 'update']);
            Route::delete('staffs/{staff}', [StaffController::class, 'destroy']);

            // // Purchases
            // Route::get('purchases', [PurchaseController::class, 'index']);
            // Route::get('purchaseReport', [PurchaseController::class, 'reportPurchase']);
            // Route::get('purchases/{purchase}', [PurchaseController::class, 'show']);
            // Route::post('purchases', [PurchaseController::class, 'store']);
            // Route::put('purchases/{purchase}', [PurchaseController::class, 'update']);
            // Route::delete('purchases/{purchase}', [PurchaseController::class, 'destroy']);
            // Route::delete('purchase_items/{purchase_item}', [PurchaseController::class, 'deletePurchaseItem']);

            // Purchase Credits
            Route::post('purchase-credits', [PurchaseCreditController::class, 'store']);
            Route::delete('purchase-credits/{purchase_credit}', [PurchaseCreditController::class, 'destroy']);

            // // Stocks
            // Route::get('stocks', [StockController::class, 'index']);
            // Route::post('stocks', [StockController::class, 'store']);

            // Item Transfers
            Route::get('item-transfers', [ItemTransferController::class, 'index']);
            Route::post('item-transfers', [ItemTransferController::class, 'store']);
            Route::post('item-transfers/batchInsert', [ItemTransferController::class, 'batchInsert']);
            Route::delete('item-transfers/{item_transfer}', [ItemTransferController::class, 'destroy']);

            // Invoices
            Route::get('invoices', [InvoiceController::class, 'index']);
            Route::post('invoices', [InvoiceController::class, 'store']);
            Route::get('invoices/{invoice}', [InvoiceController::class, 'show']);
            Route::put('invoices/{invoice}', [InvoiceController::class, 'update']);
            Route::delete('invoices/{invoice}', [InvoiceController::class, 'destroy']);
            Route::delete('invoice_items/{invoice_item}', [InvoiceController::class, 'deleteInvoiceItem']);
            Route::delete('invoice_services/{invoice_service}', [InvoiceController::class, 'deleteInvoiceService']);

            // Damage Items
            Route::get('damage-items', [DamageItemController::class, 'index']);
            Route::post('damage-items', [DamageItemController::class, 'store']);
            Route::post('damage-items/batchInsert', [DamageItemController::class, 'batchInsert']);
            Route::get('damage-items/{damage_item}', [DamageItemController::class, 'show']);
            Route::put('damage-items/{damage_item}', [DamageItemController::class, 'update']);
            Route::delete('damage-items/{damage_item}', [DamageItemController::class, 'destroy']);

            // Owner Used Items
            Route::get('owner-used-items', [OwnerUsedItemController::class, 'index']);
            Route::post('owner-used-items', [OwnerUsedItemController::class, 'store']);
            Route::get('owner-used-items/{owner_used_item}', [OwnerUsedItemController::class, 'show']);
            Route::put('owner-used-items/{owner_used_item}', [OwnerUsedItemController::class, 'update']);
            Route::delete('owner-used-items/{owner_used_item}', [OwnerUsedItemController::class, 'destroy']);

            // Summy
            Route::get('daily', [InvoiceController::class, 'daily']);
            Route::get('report/sale', [ReportController::class, 'getSaleReport']);

            //  Route::get('report/sale', [ReportController::class, 'getSaleReport']);
            // Daily Fees
            Route::get('daily-fees', [DailyFeeController::class, 'index']);
            Route::post('daily-fees', [DailyFeeController::class, 'store']);
            Route::get('daily-fees/{daily_fee}', [DailyFeeController::class, 'show']);
            Route::put('daily-fees/{daily_fee}', [DailyFeeController::class, 'update']);
            Route::delete('daily-fees/{daily_fee}', [DailyFeeController::class, 'destroy']);
        });


        Route::get('items/{uuid}/download', [ItemController::class, 'download']);

        if (App::environment('local')) {
            Route::get('routes', function () {
                $routes = [];

                foreach (Route::getRoutes()->getIterator() as $route) {
                    if (strpos($route->uri, 'api') !== false) {
                        $routes[] = $route->uri;
                    }
                }

                return response()->json($routes);
            });
        }
    });
});


Route::fallback(function () {
    return response()->json([
        'message' => 'Page Not Found. If error persists, contact www.rcs-mm.com'
    ], 404);
});
