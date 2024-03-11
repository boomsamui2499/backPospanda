<?php

namespace App\Http\Controllers;

use App\Models\Out_of_stock;
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

use Illuminate\Http\Request;

class OutOfStockController extends Controller
{
    private function datetimecurrent()
    {
        $dt = Carbon::now()->timezone('Asia/Bangkok');
        $toDay = $dt->format('d');
        $toMonth = $dt->format('m');
        $toYear = $dt->format('Y');
        $dateUTC = Carbon::createFromDate($toYear, $toMonth, $toDay, 'UTC');
        $datetimecurrent = Carbon::createFromDate($toYear, $toMonth, $toDay, 'Asia/Bangkok');
        return $datetimecurrent;
    }
    public function index()
    {

        $data = Out_of_stock::with('product')->where('active', 1)->get();
        if ($data) {
            return response()->json([
                "success" => true,
                "message" => "Data found.",
                "data" =>  $data
            ]);
        } else {
            return response()->json([
                "success" => FALSE,
                "message" => "Data not found.",
            ]);
        }
    }
    public function add(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'product_id' => 'required',
            'out_of_stock_qty' => 'required',
        ]);
        if ($validator->fails()) {
            return response()->json([
                "status" => false,
                "messageError" => $validator->errors()->toJson()
            ], 400);
        }
        $product_id = $request->input('product_id');
        $out_of_stock_qty = $request->input('out_of_stock_qty');
        $find_productID = Out_of_stock::select('*')->where('product_id', $request->input("product_id"))->get();
        // var_dump($paymentName[0]);
        // die;
        if ($find_productID->count() == 0) {
            Out_of_stock::insert([
                'product_id' => $product_id,
                "out_of_stock_qty" => $out_of_stock_qty
            ]);
            return response()->json([
                "success" => true,
                "message" => "Out of stock created new  successfully.",
            ]);
        }
        if ($find_productID->count() >= 1 && $find_productID[0]->active == 0) {
            Out_of_stock::where('product_id', $find_productID[0]->product_id)->update(['active' => 1, "out_of_stock_qty" => $out_of_stock_qty]);
            return response()->json([
                "success" => true,
                "message" => "Out of stock created successfully.",
            ]);
        } elseif ($find_productID->count() >= 1 && $find_productID[0]->active == 1) {
            return response()->json([
                "success" => FALSE,
                "message" => "Product ID  duplicate.",
            ]);
        } else {
            return response()->json([
                "success" => FALSE,
                "message" => "Out of stock fail ",
            ]);
        }
    }


    public function del($id)
    {
        try {

            Out_of_stock::where('out_of_stock_id', $id)->update(['active' => 0]);
            return response()->json([
                "success" => true,
                "message" => "Out of stock deleted successfully.",
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                "success" => FALSE,
                "message" =>"Out of stockleted fail.",
            ]);
        }
    }
}
