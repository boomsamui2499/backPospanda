<?php

namespace App\Http\Controllers;

use App\Models\Pos_pin;
use App\Models\Product;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Arr;
use App\Http\Resources\PospinResource;
use Carbon\Carbon;

class PospinController extends Controller
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
        $data = Pos_pin::orderBy('sequence', 'asc')->where("active", 1)->get();
        // $data = Pos_pin::all();
        // $products = PospinResource::collection(Pos_pin::all()->where("active", 1));
        $products = PospinResource::collection($data);
        $count = Pos_pin::select('*')->where("active", 1)->count();
        $new = array();
        $jsonarray = json_decode(json_encode($products), TRUE);
        for ($i = 0; $i < $count; $i++) {
            $obj = $jsonarray[$i]["product"];
            $obj["image_url"] = $jsonarray[$i]["image_url"];
            $obj["pos_pin_id"] = $jsonarray[$i]["pos_pin_id"];
            $obj["sequence"] = $jsonarray[$i]["sequence"];
            array_push($new, $obj);
        }
        if ($new) {
            return response()->json([
                "success" => true,
                "message" => "Pospin found.",
                "data" =>  $new
            ]);
        } else {
            return response()->json([
                "success" => FALSE,
                "message" => "Pospin not found.",
            ]);
        }
    }
    public function add(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'product_id' => 'required',
            'sequence' => 'required',
        ]);
        if ($validator->fails()) {
            return response()->json([
                "status" => false,
                "messageError" => $validator->errors()->toJson()
            ], 400);
        }
        $product_id = $request->input('product_id');
        $sequence = $request->input('sequence');
        $created_datetime = $this->datetimecurrent();
        $data = [];

        $find_productID = Pos_pin::select('*')->where('product_id', $request->input("product_id"))->get();
        // var_dump($paymentName[0]);
        // die;
        if ($find_productID->count() == 0) {
            Pos_pin::insert([
                'product_id' => $product_id,
                'created_datetime' => $created_datetime,
                "sequence" => $sequence
            ]);
            return response()->json([
                "success" => true,
                "message" => "Pospin created new  successfully.",
            ]);
        }
        if ($find_productID->count() >= 1 && $find_productID[0]->active == 0) {
            Pos_pin::where('product_id', $find_productID[0]->product_id)->update(['active' => 1, "sequence" => $sequence]);
            return response()->json([
                "success" => true,
                "message" => "Pospin created successfully.",
            ]);
        } elseif ($find_productID->count() >= 1 && $find_productID[0]->active == 1) {
            return response()->json([
                "success" => FALSE,
                "message" => "Product ID  duplicate.",
            ]);
        } else {
            return response()->json([
                "success" => FALSE,
                "message" => "Pospin fail ",
            ]);
        }
    }


    public function del($id)
    {
        try {

            Pos_pin::where('pos_pin_id', $id)->update(['active' => 0]);
            return response()->json([
                "success" => true,
                "message" => "Pospin deleted successfully.",
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                "success" => FALSE,
                "message" => "Pospin deleted fail.",
            ]);
        }
    }
}
