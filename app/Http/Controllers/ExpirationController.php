<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;
use App\Models\Expiration_log;


class ExpirationController extends Controller
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
        $data =
            Expiration_log::with('product')->where('active', 1)->get();

        return response()->json([
            "success" => true,
            "message" => "Expiration List",
            "data" => $data
        ]);
    }

    public function add(Request $request)
    {
        $created_datetime = $this->datetimecurrent(); //datatimecurrent
        $validator = Validator::make($request->all(), [
            'product_id' => 'required',
            'lot_number' => 'required',
            'expired_datetime' => 'required',

        ]);
        if ($validator->fails()) {
            return response()->json([
                "status" => false,
                "messageError" => $validator->errors()->toJson()
            ], 400);
        }
        $data = [
            'product_id' => $request->input('product_id'),
            'lot_number' => $request->input('lot_number'),
            'expired_datetime' => $request->input('expired_datetime'),
            "created_datetime" => $created_datetime
        ];


        $expiration_log_id = Expiration_log::insertGetId($data);

        $expiration_log = Expiration_log::select('*')->where("expiration_log_id", $expiration_log_id)->get();
        if ($expiration_log->count() == 1) {
            return response()->json([
                "success" => true,
                "message" => "Expiration log created successfully.",
            ]);
        } else {
            return response()->json([
                "success" => false,
                "message" => "Expiration log created fail.",
            ]);
        }
        // $lot_number = Expiration_log::select('*')->where('lot_number', $request->input("lot_number"))->get();

        // if ($lot_number->count() == 0) {
        //     Expiration_log::insert($data);
        //     return response()->json([
        //         "success" => true,
        //         "message" => "Expiration log created new successfully.",
        //     ]);
        // }
        // if ($lot_number->count() >= 1 && $lot_number[0]->active == 0) {
        //     $data["active"] = 1;

        //     Expiration_log::where('expiration_log_id', $lot_number[0]->expiration_log_id)->update($data);
        //     return response()->json([
        //         "success" => true,
        //         "message" => "Expiration log created successfully.",
        //     ]);
        // } elseif ($lot_number->count() >= 1 && $lot_number[0]->active == 1) {
        //     return response()->json([
        //         "success" => FALSE,
        //         "message" => "Expiration lot number  duplicate.",
        //     ]);
        // } else {
        //     return response()->json([
        //         "success" => FALSE,
        //         "message" => "Expiration log fail.",
        //     ]);
        // }
    }
    public function del($id)
    {
        try {
            Expiration_log::where('expiration_log_id', $id)->update(['active' => 0]);
            return response()->json([
                "success" => true,
                "message" => "Expiration log deleted successfully.",
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                "success" => FALSE,
                "message" => "Expiration log deleted fail.",
            ]);
        }
    }
}
