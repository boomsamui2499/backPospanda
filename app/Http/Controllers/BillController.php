<?php

namespace App\Http\Controllers;

use App\Models\bill;
use App\Models\billDetail;
use App\Models\billcurrent;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

use thiagoalessio\TesseractOCR\TesseractOCR;
use Carbon\Carbon;

class BillController extends Controller
{
  /**
   * Display a listing of the resource.
   *
   * @return \Illuminate\Http\Response
   */
  public function showbill(Request $request)
  {

    $startDate = $request->input('startDate');
    $endDate = $request->input('endDate');
    $startDateCur = Carbon::now()->timezone('Asia/Bangkok')->subDays(7)->format('Y-m-d');
    $endDateCur = Carbon::now()->timezone('Asia/Bangkok')->format('Y-m-d');
    // $endDate = Carbon::now()->timezone('Asia/Bangkok')->addDays(10)->endOfDay()->format('Y-m-d');
    if (!$startDate || !$endDate) {
      $data = DB::table('bill')->select('*')->where('date', '>=', $startDateCur)->where('date', '<=', $endDateCur)->orderBy('date', 'desc')->orderBy('time', 'desc')->get();
    } else {
      $data = DB::table('bill')->select('*')->where('date', '>=', $startDate)->where('date', '<=', $endDate)->orderBy('date', 'desc')->orderBy('time', 'desc')->get();
    }

    //ASC 
    return response()->json([
      "success" => true,
      "message" => "bill List",
      "data" => $data
    ]);
  }
  public function showbilldetail($bill_id)
  {
    $data =
      DB::table('bill_detail')
      ->join('products', 'bill_detail.product_id', '=', 'products.product_id')
      ->select('count', 'product_name', 'product_price')
      // ->select('*')
      ->where('bill_detail.bill_id', $bill_id)
      ->get();
    return response()->json([
      "success" => true,
      "message" => "bill List",
      "data" => $data
    ]);
  }

  public function addBillCurrent(Request $request)
  {

    $product_id = $request->input('product_id');
    $price = $request->input('price');
    // $count = $request->input('count');
    try {
      $data = DB::table('bill_current')->select('*')->where('product_id', $product_id)->get();

      if ($data->isEmpty()) {
        DB::table('bill_current')->insert([
          'product_id' => $product_id,
          'price' => $price,
          'count' => 1
        ]);
      } else {

        $count = $data[0]->count + 1;
        DB::table('bill_current')->where('product_id', $product_id)
          ->update(['count' => $count]);
      }

      return response()->json([
        "success" => true,
        "message" => "successfully.",
      ]);
    } catch (\Throwable $e) {
      throw $e;
    }
  }

  public function updateBillCurrent(Request $request, $id)
  {
    // $product_id = $request->input('product_id');
    $count = $request->input('count');
    try {
      DB::table('bill_current')->where('bill_current_id', $id)->update(['count' => $count]);
      return response()->json([
        "success" => true,
        "message" => $count,
      ]);
    } catch (\Throwable $e) {
      throw $e;
    }
  }
  public function delBillCurrent($id)
  {

    try {
      DB::table('bill_current')->where('bill_current_id', $id)->delete();
      return response()->json([
        "success" => true,
        "message" => "Delete successfully.",
      ]);
    } catch (\Throwable $e) {
      throw $e;
    }
  }
  public function showbillcurrent()
  {
    $data =
      DB::table('bill_current')
      ->join('products', 'bill_current.product_id', '=', 'products.product_id')
      ->select('bill_current_id', 'count', 'product_name', 'price')
      ->get();
    return response()->json([
      "success" => true,
      "message" => "bill List",
      "data" => $data
    ]);
  }
  public function showtotalprice()
  {
    $data =
      DB::table('bill_current')->select(DB::raw('sum(price * count) as total'))->get();
    return response()->json([
      "success" => true,
      "message" => "bill List",
      "totalprice" => $data
    ]);
  }



  public function addbilldetail(Request $request)
  {
    $date = Carbon::now()->timezone('Asia/Bangkok')->format('Y-m-d');
    $time = Carbon::now()->timezone('Asia/Bangkok')->format('H:i:s');
    // $total_price = $request->input('total_price');

    $array = array();
    $billcurrentdata = DB::table('bill_current')->get();
    $Total_price = DB::table('bill_current')->select(DB::raw('sum(price * count) as total'))->get();
    DB::beginTransaction();

    try {
      DB::table('bill')->insert([
        'date' => $date,
        'time' => $time,
        'total_price' => $Total_price[0]->total
      ]);
      $billdata = DB::table('bill')->orderBy('bill_id', 'desc')->first();

      foreach ($billcurrentdata as $data) {
        $array[] = array(
          'bill_id' => $billdata->bill_id,
          'product_id' => $data->product_id,
          'count' => $data->count,
          'price' => $data->price
        );
      }
      DB::table('bill_detail')->insert($array);
      DB::table('bill_current')->delete();
      // DB::table('bill_currentdasd')->delete();

      DB::commit();

      return response()->json([
        "success" => true,
      ]);
    } catch (\Throwable $e) {
      DB::rollback();

      return response()->json([
        "success" => false,
      ]);
    }
  }

  public function showlastbill()
  {
    $billdata = DB::table('bill')->orderBy('bill_id', 'desc')->first();
    $data = DB::table('bill')
      ->join('bill_detail', 'bill.bill_id', '=', 'bill_detail.bill_id')
      ->join('products', 'bill_detail.product_id', '=', 'products.product_id')->where('bill.bill_id', $billdata->bill_id)
      // ->groupBy('bill_detail.bill_id')
      ->get();
    // ->orderBy('bill.bill_id', 'desc');
    return response()->json([
      "success" => true,
      "message" => "bill List",
      "data" => $data
    ]);
  }
}
