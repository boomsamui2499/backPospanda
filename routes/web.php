<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\PricelistController;
use App\Http\Controllers\FileUploadController;
use App\Http\Controllers\sessionController;
use App\Http\Controllers\UserController;

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

Route::get('/', function () {
    return view('welcome');
});
//เรียกtoken
Route::get('/token', function () {
    return csrf_token();
});
Route::post('upload', [FileUploadController::class, 'store']);
// สินค้า
Route::get('/product', [ProductController::class, 'index']);
Route::get('/product/search/{name}', [ProductController::class, 'search']);
Route::get('/product/searchCatagory/{id}', [ProductController::class, 'showFitterCatagory']);
Route::get('/product/{id}', [ProductController::class, 'show']);
Route::post('/product/add', [ProductController::class, 'add']);
Route::post('/product/{id}/update', [ProductController::class, 'update']);
Route::put('/product/{id}/delete', [ProductController::class, 'del']);

//หมวดหมู่
Route::get('/category', [CategoryController::class, 'index']);
Route::get('/category/{id}', [CategoryController::class, 'show']);
Route::post('/category/add', [CategoryController::class, 'add']);
Route::post('/category/{id}/update', [CategoryController::class, 'update']);
Route::put('/category/{id}/delete', [CategoryController::class, 'del']);

// รายการราคา
Route::get('/pricelist', [PricelistController::class, 'index']);
Route::get('/pricelist/{id}', [PricelistController::class, 'show']);
Route::post('/pricelist/add', [PricelistController::class, 'add']);
Route::post('/pricelist/{id}/update', [PricelistController::class, 'update']);
Route::put('/pricelist/{id}/delete', [PricelistController::class, 'del']);

//จัดการนพนักงาน
Route::get('/user', [UserController::class, 'index']);
Route::get('/user/{id}', [UserController::class, 'show']);
Route::post('/user/add', [UserController::class, 'add']);
Route::post('/user/{id}/update', [UserController::class, 'update']);
Route::post('/user/{id}/updatepassword', [UserController::class, 'updatePassword']);
Route::put('/user/{id}/delete', [UserController::class, 'del']);

//ปิดกะเปิดกะ
Route::get('/check', [sessionController::class, 'checkSession']);
Route::post('/open', [sessionController::class, 'open']);
Route::post('/close', [sessionController::class, 'close']);


// //รายละเอียดบิล
// Route::get('/bill', [BillController::class, 'showbill']);
// Route::get('/billDetail/{bill_id}', [BillController::class, 'showbilldetail']);
// Route::get('/bill/add', [BillController::class, 'addbilldetail']);

// //บิลปัจจุบัน
// Route::delete('/billCurrent/{id}/delete', [BillController::class, 'delBillCurrent']);
// Route::post('/billCurrent/add', [BillController::class, 'addBillCurrent']);
// Route::get('/billCurrent', [BillController::class, 'showbillcurrent']);
// Route::get('/billLast', [BillController::class, 'showlastbill']);
// Route::get('/billCurrent/price', [BillController::class, 'showtotalprice']);
// Route::put('/billCurrent/{id}/update', [BillController::class, 'updateBillCurrent']);
