<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\PricelistController;
use App\Http\Controllers\FileUploadController;
use App\Http\Controllers\sessionController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\MemberController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\TambonController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\PurchaseOrderController;
use App\Http\Controllers\PospinController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ExpirationController;
use App\Http\Controllers\StockAdjustmentController;
use App\Http\Controllers\MetaDataController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\NoteCoffeeController;
use App\Http\Controllers\PrintController;
use App\Http\Controllers\PromotinController;
use App\Http\Controllers\OutOfStockController;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

use App\Imports\ProductImport;
use App\Models\MetaData;
use App\Models\Order_lines;
use App\Models\Order_payment;
use App\Models\Orders;
use App\Models\Product;
use Milon\Barcode\QRcode as BarcodeQRcode;
// use Phattarachai\LineNotify\Facade\Line;
use Phattarachai\LineNotify\Line;



/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes foccccccccccccccccccccssssssssssssssssssssssssssssssssssr your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/
// Route::resource('product', 'App\Http\Controller\ProductController');


Route::get('/provinces', [TambonController::class, 'getProvinces']);
Route::get('/amphoes', [TambonController::class, 'getAmphoes']);
Route::get('/tambons', [TambonController::class, 'getTambons']);
Route::get('/zipcodes', [TambonController::class, 'getZipcodes']);

Route::group([
    'middleware' => 'api',
    'prefix' => 'auth'
], function ($router) {
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/register', [AuthController::class, 'register'])->middleware('userrole:owner');
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/refresh', [AuthController::class, 'refresh']);
    Route::get('/user-profile', [AuthController::class, 'userProfile']);
});

//หมวดหมู่
Route::group([
    'middleware' => ['api', 'auth'],
    'prefix' => 'category'
], function ($router) {
    Route::get('/aa', [CategoryController::class, 'index'])->middleware('userrole:cashier');
    Route::get('/bb', [CategoryController::class, 'index'])->middleware('userrole:manager');
    Route::get('/', [CategoryController::class, 'index']);
    Route::get('/{id}', [CategoryController::class, 'show']);
    Route::post('/add', [CategoryController::class, 'add']);
    Route::post('/{id}/update', [CategoryController::class, 'update']);
    Route::put('/{id}/delete', [CategoryController::class, 'del']);
    Route::get('/search/{name}', [CategoryController::class, 'search']);
});

//สินค้า

Route::group([
    'middleware' => ['api', 'auth'],
], function ($router) {

    Route::post('upload', [FileUploadController::class, 'store']);
    Route::post('import', [FileUploadController::class, 'import']);
    Route::post('import/stock', [FileUploadController::class, 'importStock']);
});
// Route::group([
//     // 'middleware' => ['api'],
// ], function ($router) {

//     Route::post('upload', [FileUploadController::class, 'storelink']);
//     // Route::post('import', [FileUploadController::class, 'import']);
//     // Route::post('import/stock', [FileUploadController::class, 'importStock']);
// });



Route::group([
    'middleware' => ['api', 'auth'],
    'prefix' => 'product'
], function ($router) {
    Route::get('/', [ProductController::class, 'index']);
    Route::get('/search/{name}', [ProductController::class, 'search']);
    Route::get('/searchBarcode/{barcode}/{price_list}', [ProductController::class, 'searchBarcode']);
    Route::get('/searchcategory/Pricelist_idAndCategory_id/{pospin_id}/{category_id}/{pricelist_id}', [ProductController::class, 'searchPricelist_idAndCategory_id']);
    Route::get('/searchCatagory/{id}', [ProductController::class, 'showFitterCatagory']);
    Route::get('/{id}', [ProductController::class, 'show']);
    Route::post('/add', [ProductController::class, 'add']);
    Route::post('/addNew', [ProductController::class, 'addNew']);
    Route::post('/{id}/update', [ProductController::class, 'update']);
    Route::put('/{id}/delete', [ProductController::class, 'del'])->middleware('userrole:owner');
});

// รายการราคา
Route::group([
    'middleware' => ['api', 'auth'],
    'prefix' => 'pricelist'
], function ($router) {
    Route::get('/', [PricelistController::class, 'index']);
    Route::get('/{id}', [PricelistController::class, 'show']);
    Route::post('/add', [PricelistController::class, 'add'])->middleware('userrole:owner');
    Route::post('/{id}/update', [PricelistController::class, 'update'])->middleware('userrole:owner');
    Route::put('/{id}/delete', [PricelistController::class, 'del'])->middleware('userrole:owner');
});

//จัดการนพนักงาน
Route::group([
    'middleware' => ['api', 'auth'],
    'prefix' => 'user'
], function ($router) {
    Route::get('/', [UserController::class, 'index']);
    Route::get('/{id}', [UserController::class, 'show']);
    Route::post('/add', [UserController::class, 'add'])->middleware('userrole:owner');
    Route::post('/{id}/update', [UserController::class, 'update'])->middleware('userrole:owner');
    Route::post('/{id}/updatepassword', [UserController::class, 'updatePassword'])->middleware('userrole:owner');
    Route::put('/{id}/delete', [UserController::class, 'del'])->middleware('userrole:owner');
});

//จัดการสมาชิก
Route::group([
    'middleware' => ['api', 'auth'],
    'prefix' => 'member'
], function ($router) {
    Route::get('/', [MemberController::class, 'index']);
    Route::get('/{id}', [MemberController::class, 'showFitterMember']);
    Route::post('/add', [MemberController::class, 'add']);
    Route::post('/{id}/update', [MemberController::class, 'update']);
    Route::put('/{id}/delete', [MemberController::class, 'del']);
    Route::get('/history/{id}', [MemberController::class, 'historyBuy']);
    Route::get('/report/debt', [MemberController::class, 'DebtReport']);
    Route::get('/search/{name}', [MemberController::class, 'search']);
    Route::get('/searchName/{name}', [MemberController::class, 'searchNameLastname']);
});

//จัดการการชำระเงิน
Route::group([
    'middleware' => ['api', 'auth'],
    'prefix' => 'payment'
], function ($router) {
    Route::get('/', [PaymentController::class, 'index']);
    Route::get('/{id}', [PaymentController::class, 'showFitterpayment']);
    Route::post('/add', [PaymentController::class, 'add'])->middleware('userrole:manager');
    Route::post('/{id}/update', [PaymentController::class, 'update'])->middleware('userrole:manager');
    Route::put('/{id}/delete', [PaymentController::class, 'del'])->middleware('userrole:manager');
});



//session
Route::group([
    'middleware' => ['api', 'auth'],
    'prefix' => 'session'
], function ($router) {

    Route::get('/check', [sessionController::class, 'checkSession'])->middleware('userrole:cashier');
    Route::get('/check/allbill', [sessionController::class, 'checkBillLastSession'])->middleware('userrole:cashier');
    Route::post('/open', [sessionController::class, 'open'])->middleware('userrole:cashier');
    Route::post('/close', [sessionController::class, 'close'])->middleware('userrole:cashier');
});

// จัดการซัพพายเออร์
Route::group([
    'middleware' => ['api', 'auth'],
    'prefix' => 'supplier'
], function ($router) {
    Route::get('/', [SupplierController::class, 'index'])->middleware('userrole:manager');
    Route::get('/{id}', [SupplierController::class, 'showbyid'])->middleware('userrole:manager');
    Route::post('/add', [SupplierController::class, 'add'])->middleware('userrole:manager');
    Route::post('/{id}/update', [SupplierController::class, 'update'])->middleware('userrole:manager');
    Route::put('/{id}/delete', [SupplierController::class, 'del'])->middleware('userrole:manager');
});

// ซื้อสินค้าเข้าร้าน
Route::group([
    'middleware' => ['api', 'auth'],
    'prefix' => 'purchaseorder'
], function ($router) {

    Route::get('/', [PurchaseOrderController::class, 'index'])->middleware('userrole:manager');
    Route::get('/gen/id', [PurchaseOrderController::class, 'GenPuchaseID'])->middleware('userrole:manager');
    Route::get('/{id}', [PurchaseOrderController::class, 'showbyid'])->middleware('userrole:manager');
    Route::post('/addedit', [PurchaseOrderController::class, 'addedit'])->middleware('userrole:manager');
    Route::post('/{id}/save', [PurchaseOrderController::class, 'save'])->middleware('userrole:manager');
    Route::post('/{id}/cancel', [PurchaseOrderController::class, 'cancel'])->middleware('userrole:manager');
    // Route::put('/{id}/delete', [PurchaseOrderController::class, 'del'])->middleware('userrole:manager');
});


// ปักหมุดสินค้า
Route::group([
    'middleware' => ['api', 'auth'],
    'prefix' => 'pospin'
], function ($router) {

    Route::get('/', [PospinController::class, 'index']);
    Route::post('/add', [PospinController::class, 'add']);
    Route::put('/{id}/delete', [PospinController::class, 'del']);
});

// หน้าขาย
Route::group([
    'middleware' => ['api', 'auth'],
    'prefix' => 'sale'
], function ($router) {

    Route::post('/pay', [OrderController::class, 'pay']);
    Route::post('/refund/{order_id}', [OrderController::class, 'refund']);
});

// บิล
Route::group([
    'middleware' => ['api', 'auth'],
    'prefix' => 'bill'
], function ($router) {

    // Route::get('/', [OrderController::class, 'showbill']);
    Route::get('/{id}', [OrderController::class, 'showbyid']);
    Route::get('/billdetail/datetime', [OrderController::class, 'showbyDate']);
    Route::get('/billdetail/date', [OrderController::class, 'showDateOrderdetail']);
});
// สต๊อก
Route::group([
    'middleware' => ['api', 'auth'],
    'prefix' => 'stock'
], function ($router) {
    Route::get('/', [ProductController::class, 'stock'])->middleware('userrole:manager');
    Route::get('/search/{name}', [ProductController::class, 'searchStock'])->middleware('userrole:manager');
    Route::get('/adjustment', [StockAdjustmentController::class, 'index'])->middleware('userrole:manager');
    Route::get('/adjustment/{id}', [StockAdjustmentController::class, 'showbyid'])->middleware('userrole:manager');
    Route::post('/addedit', [StockAdjustmentController::class, 'addedit'])->middleware('userrole:manager');
    Route::post('/save', [StockAdjustmentController::class, 'save'])->middleware('userrole:manager');
});

// สินค้าหมดอายุ

Route::group([
    'middleware' => ['api', 'auth'],
    'prefix' => 'expiration'
], function ($router) {
    Route::get('/', [ExpirationController::class, 'index']);
    Route::post('/add', [ExpirationController::class, 'add']);
    Route::put('/{id}/delete', [ExpirationController::class, 'del']);
});

Route::group([
    'middleware' => ['api', 'auth'],
    'prefix' => 'system'
], function ($router) {
    Route::get('/', [MetaDataController::class, 'index']);
    Route::get('/{module}', [MetaDataController::class, 'module']);
    Route::get('/{module}/{key}', [MetaDataController::class, 'moduleAndKey']);
    Route::post('/add', [MetaDataController::class, 'add']);
    Route::post('/update', [MetaDataController::class, 'update']);
    Route::post('/updatecount', [MetaDataController::class, 'updatecount']);
    Route::get('/show/round/data', [MetaDataController::class, 'round']);
});
Route::group([
    'middleware' => ['api'],
    'prefix' => 'dashboard'
], function ($router) {
    Route::get('/purchaseordertable', [DashboardController::class, 'DashboardPurchaseTable'])->middleware('userrole:manager');
    Route::get('/purchaseorder', [DashboardController::class, 'DashboardPurchase'])->middleware('userrole:manager');
    Route::get('/purchaseorder/month', [DashboardController::class, 'DashboardPurchasemonth'])->middleware('userrole:manager');
    Route::get('/purchaseordertable/month', [DashboardController::class, 'DashboardPurchasemonthTable'])->middleware('userrole:manager');
    Route::get('/order', [DashboardController::class, 'DashboardOrder'])->middleware('userrole:manager', 'userrole:manager');
    Route::get('/order/day', [DashboardController::class, 'DashboardOrderDay'])->middleware('userrole:manager');
    Route::get('/ordertable/day', [DashboardController::class, 'DashboardOrderDayTable'])->middleware('userrole:manager');
    Route::get('/order/month', [DashboardController::class, 'DashboardOrderMonth'])->middleware('userrole:manager');
    Route::get('/ordertable/month', [DashboardController::class, 'DashboardOrderMonthTable'])->middleware('userrole:manager');
    Route::get('/expiration', [DashboardController::class, 'DashboardExpirationTable'])->middleware('userrole:manager');
    Route::get('/orderproduct', [DashboardController::class, 'DashboardOrderProduct'])->middleware('userrole:manager');
    Route::get('/orderproducttable', [DashboardController::class, 'DashboardOrderProductTable'])->middleware('userrole:manager');
    Route::get('/margin/day', [DashboardController::class, 'DashboardMarginDay'])->middleware('userrole:manager');
    Route::get('/margintable/day', [DashboardController::class, 'DashboardMarginDayTable'])->middleware('userrole:manager');
    Route::get('/bill', [DashboardController::class, 'DashboardBill'])->middleware('userrole:manager');
    Route::get('/billtable', [DashboardController::class, 'DashboardBillTable'])->middleware('userrole:manager');
    Route::get('/session', [DashboardController::class, 'DashboardSession'])->middleware('userrole:cashier');
    Route::get('/session/{id}', [DashboardController::class, 'DashboardDetailSession']);
    Route::get('/outofstock', [DashboardController::class, 'DashboardOutOfStockTable'])->middleware('userrole:manager');
});


// รายการpromotion
Route::group([
    'middleware' => ['api', 'auth'],
    'prefix' => 'promotion'
], function ($router) {
    Route::get('/', [PromotinController::class, 'index']);
    Route::post('/add', [PromotinController::class, 'add']);
    Route::put('/{id}/delete', [PromotinController::class, 'del']);
});
Route::post('/print/bill', [PrintController::class, 'generate']);
Route::get('/print/pdf', [PrintController::class, 'generatePDF']);
Route::get('/testbarcode', [PrintController::class, 'testbarcode']);
Route::get('/barcode/{barcode}', [PrintController::class, 'test']);
Route::get('/migrateFull', function () {
    Artisan::call('key:generate');
    Artisan::call('migrate:refresh');
    Artisan::call('storage:link');
    Artisan::call('db:seed');
    return response()->json([
        "success" => true,
        "message" => "Project already"
    ]);
});
Route::get('/clearCache', function () {
    Artisan::call('key:generate');
    Artisan::call('route:clear');
    Artisan::call('config:clear');
    Artisan::call('view:clear');
    Artisan::call('cache:clear');
    return response()->json([
        "success" => true,
        "message" => "clear cache already"
    ]);
});
Route::get('/migrate', function () {
    Artisan::call('migrate');
    return response()->json([
        "success" => true,
        "message" => "Migrate already"
    ]);
});
Route::get('/billtable', [DashboardController::class, 'DashboardBillTable']);
Route::post('/clear', [AuthController::class, 'clearData']);
Route::post('/dump', [AuthController::class, 'dumpData']);
Route::post('/zip', [AuthController::class, 'download']);

Route::get('/link', function () {

    Artisan::call('storage:link');
    return response()->json([
        "success" => true,
        "message" => "Project storage link already"
    ]);
});
Route::get('/clear-cache', function () {
    Artisan::call('cache:clear');
    return 'Application cache has been cleared';
});

Route::get('/demo', function () {

    return response()->json([
        "success" => true,
        "is_demo" => \config('getURL.is_demo')

    ]);
});

Route::get('/windown', function () {

    return response()->json([
        "success" => true,
        "is_windown" => (int)env('IS_WINDOWN')

    ]);
});
Route::get('/exp', function () {

    return response()->json([
        "success" => true,
        "is_exp" => \config('getURL.exp_date')

    ]);
});
Route::get('/image/qrcode', function () {

    $data = "public/barcode/qrcode.png";
    return response()->json([
        "success" => true,
        "url" => url('/') . "/storage" . str_replace("public", "", $data)
    ]);
});
Route::group([
    'middleware' => ['api', 'auth'],
    'prefix' => 'outOfStock'
], function ($router) {
    Route::get('/', [OutOfStockController::class, 'index']);
    Route::post('/add', [OutOfStockController::class, 'add']);
    // Route::post('/update', [MetaDataController::class, 'update']);
    Route::put('/{id}/delete', [OutOfStockController::class, 'del']);
});
// note coffee
Route::group([
    'middleware' => ['api'],
    'prefix' => 'note'
], function ($router) {
    Route::get('/', [NoteCoffeeController::class, 'index']);
    Route::post('/add', [NoteCoffeeController::class, 'add']);
    Route::post('/{id}/update', [NoteCoffeeController::class, 'update']);
    Route::put('/{id}/delete', [NoteCoffeeController::class, 'del']);
});
Route::post('uploadbranch', [FileUploadController::class, 'storelink']);
Route::post('update/branch', [MetaDataController::class, 'updateBranch']);
Route::post('sync/product', [ProductController::class, 'syncProduct']);
Route::post('/transfer/upstock', [StockAdjustmentController::class, 'upStock']);
Route::post('/transfer/downstock', [StockAdjustmentController::class, 'downStock']);
Route::get('/show/stock', [StockAdjustmentController::class, 'stock']);
Route::get('/show/dashboard/stock', [StockAdjustmentController::class, 'stockName']);
Route::get('/show/stockAdjust', [StockAdjustmentController::class, 'showdate']);
Route::get('/show/session', [DashboardController::class, 'DashboardSessionBranch']);
Route::get('/show/adjustment/{id}', [StockAdjustmentController::class, 'showbyid']);
Route::get('/show/session/{id}', [DashboardController::class, 'DashboardDetailSession']);
Route::get('/daily/sale', [DashboardController::class, 'DashboardDailySale']);
Route::get('/show/daily/sale/chart', [DashboardController::class, 'DashboardDailySaleChart']);
Route::get('/show/order/product', [DashboardController::class, 'DashboardOrderProductTable']);
Route::get('/testmem', [OrderController::class, 'testmem']);
