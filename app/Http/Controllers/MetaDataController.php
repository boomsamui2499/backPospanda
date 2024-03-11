<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\MetaData;
use Symfony\Component\Console\Input\Input;

class MetaDataController extends Controller
{
    public function index()
    {
        $Data = MetaData::select('*')->where("active", 1)->get();
        return response()->json([
            "success" => true,
            "message" => "Data List.",
            "data" => $Data
        ]);
    }
    public function module($module)
    {
        $Data = MetaData::where("active", 1)->where("meta_module", $module)->get();
        if ($Data->count() >= 1) {
            return response()->json([
                "success" => true,
                "message" => "Data List.",
                "data" => $Data
            ]);
        } else {
            return response()->json([
                "success" => false,
                "message" => "Data not found.",
            ]);
        }
    }

    public function moduleAndKey($module, $key)
    {
        $Data = MetaData::where("active", 1)->where("meta_module", $module)->where("meta_key", $key)->get();
        if ($Data->count() >= 1) {
            return response()->json([
                "success" => true,
                "message" => "Data List.",
                "data" => $Data
            ]);
        } else {
            return response()->json([
                "success" => false,
                "message" => "Data not found.",
            ]);
        }
    }

    public function add(Request $request)
    {
        $jsonarray = json_decode(json_encode($request->input()), TRUE);
        $new = array();
        foreach ($jsonarray as $data) {
            array_push($new, $data);
        }
        if (!$new == []) {
            MetaData::insert($new);
            return response()->json([
                "success" => true,
                "message" => "system created successfully.",
            ]);
        } else {
            return response()->json([
                "success" => FALSE,
                "message" => "system created fail.",
            ]);
        }
    }
    public function updateCount(Request $request)
    {

        $update = MetaData::where('meta_id', $request->input('meta_id'))->update($request->input());
        if ($update == 0) {
            return response()->json([
                "success" => false,
                "message" => "can't update system data.",
            ]);
        }
        return response()->json([
            "success" => true,
            "message" => "update system successfully.",
        ]);
    }
    public function update(Request $request)
    {
        $jsonarray = json_decode(json_encode($request->input()), TRUE);
        foreach ($jsonarray as $data) {
            $update = MetaData::where('meta_id', $data["meta_id"])->update($data);
            if ($update == 0) {

                return response()->json([
                    "success" => false,
                    "message" => "can't update system data.",
                    "Data" => $data,
                ]);
            }
        }
        return response()->json([
            "success" => true,
            "message" => "update system successfully.",
        ]);
    }
    public function updateBranch(Request $request)
    {
        $datatoken = MetaData::select('meta_value')->where("active", 1)->where("meta_module", 'branch')->where("meta_key", 'branch_token')->first();
        $token_for_db = (int)$datatoken->meta_value;
        $token_for_required = (int)$request->input('token');
        // error_log($token_for_db);
        // error_log($token_for_required);
        // var_dump($token_for_db, $token_for_required);
        if ($token_for_db == $token_for_required) {
            var_dump($token_for_db, $token_for_required);
            $jsonarray = json_decode(json_encode($request->input('meta_value')), TRUE);
            $update = MetaData::where('meta_key', $request->input('meta_key'))->update(["meta_value" => $jsonarray]);
            if ($update == 0) {

                return response()->json([
                    "success" => false,
                    "message" => "can't update system data.",
                    "Data" => $jsonarray,
                ], 400);
            }
            return response()->json([
                "success" => true,
                "message" => "update system successfully.",
            ], 200);
        } else {
            return response()->json([
                "status" => false,
                "message" => "Token ไม่เหมือนกัน "
            ], 401);
        }
    }
    public function round(Request $request)
    {
        $total_pay = $request->input('total');
        // $cal = 300.1;
        $Data = MetaData::select("meta_value")->where("active", 1)->where("meta_module", "store")->where("meta_key", "store_round")->get();
        if ($Data[0]->meta_value == 1) {
            $total = number_format((float)$total_pay, 2, '.', '');
            $payment_amount_ceil = ceil($total);
            $payment_amount = number_format((float)$payment_amount_ceil, 2, '.', '');
            $rounddata = $total - $payment_amount;
            $roundfi = number_format((float)$rounddata, 2, '.', '');
            return response()->json([
                "success" => true,
                "message" => "calculate round 0.1 up ",
                "total" => $total,
                "payment_amount" => $payment_amount,
                "round" => $roundfi

            ]);
        } elseif ($Data[0]->meta_value == 2) {
            $total = number_format((float)$total_pay, 2, '.', '');
            $payment_amount = round($total);
            $rounddata = $total - $payment_amount;
            $roundfi = number_format((float)$rounddata, 2, '.', '');
            return response()->json([
                "success" => true,
                "message" => "calculate round 0.5 up ",
                "total" => $total,
                "payment_amount" => $payment_amount,
                "round" => $roundfi

            ]);
        } else {
            return response()->json([
                "success" => false,
                "message" => " not found round data.",
            ]);
        }
    }
}
