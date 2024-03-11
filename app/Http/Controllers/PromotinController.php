<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use App\Models\Promotion;

class PromotinController extends Controller
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
        $data = Promotion::select('promotion_id', 'promotion_name', 'product_id', 'price', 'point', 'qty')->with('product')->where('active', 1)->get();
        $dataPromotion = array();
        foreach ($data as $value) {
            $obj['promotion_id'] = $value->promotion_id;
            $obj['promotion_name'] = $value->promotion_name;
            $obj['product_id'] = $value->product_id;
            $obj['product_name'] = $value->product->product_name;
            $obj['price'] = $value->price;
            $obj['point'] = $value->point;
            $obj['qty'] = $value->qty;
            array_push($dataPromotion, $obj);
        }


        return response()->json([
            "success" => true,
            "message" => "Promotion List",
            "data" => $dataPromotion
        ]);
    }
    public function add(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'promotion_name' => 'required|string|between:2,100',
            'product_id' => 'required|integer',
            'price' => 'required|numeric',
            'point' => 'required|integer',
            'qty' => 'required|integer',
        ]);
        if ($validator->fails()) {
            return response()->json([
                "status" => false,
                "messageError" => $validator->errors()->toJson()
            ], 400);
        }
        $registered_date = $this->datetimecurrent();
        if ($request->input()) {
            Promotion::insert([
                'promotion_name' => $request->input('promotion_name'),
                'product_id' => $request->input('product_id'),
                'price' => $request->input('price'),
                'point' => $request->input('point'),
                'qty' => $request->input('qty'),
            ]);
            return response()->json([
                "success" => true,
                "message" => "Promotion created successfully.",
            ]);
        } else {
            return response()->json([
                "success" => false,
                "message" => "Promotion created fail.",
            ]);
        }
    }
    public function del($id)
    {
        try {
            Promotion::where('Promotion_id', $id)->update(['active' => 0]);
            return response()->json([
                "success" => true,
                "message" => "Promotion deleted successfully.",
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                "success" => FALSE,
                "message" => "Promotion deleted fail.",
            ]);
        }
    }
}
