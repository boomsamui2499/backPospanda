<?php

namespace App\Http\Controllers;

use App\Imports\ImportStock;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
// use Illuminate\Support\Facades\Excel;
use App\Models\Product;
use Illuminate\Http\Response;
use Excel;
use App\Imports\ProductImport;
use App\Models\MetaData;
use App\Models\Stock_adjustment;
use App\Models\Stock_adjustment_line;
use App\Models\Stock_move;
use Carbon\Carbon;
use Illuminate\Support\Arr;

class FileUploadController extends Controller
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
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required|image',
        ]);
        if ($validator->fails()) {
            return response()->json([
                "status" => 500,
                "message" => $validator->errors()
            ]);
        }
        $userprofile = $request->input('userprofile');
        if ($userprofile) {
            $path = 'public/files/';
            $path .= $userprofile;
            $path = $request->file('file')->store($path);
            return response()->json([
                "status" => 200,
                "message" => "Upload image successfully.",
                "upload_data" => [
                    // "url" => url('/')."/storage".str_replace("public","",$path),
                    "full_path" => url('/') . "/storage" . str_replace("public", "", $path),
                    "path" => $path,
                ]
            ]);
        } else {
            $file = $request->file('file');
            $name = $request->file('file')->getClientOriginalName();
            $path = $request->file('file')->store('public/files');
            return response()->json([
                "status" => 200,
                "message" => "Upload image successfully.",
                "upload_data" => [
                    // "url" => url('/')."/storage".str_replace("public","",$path),
                    "full_path" => url('/') . "/storage" . str_replace("public", "", $path),
                    "path" => $path,
                ]
            ]);
        }
    }
    public function storelink(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required|image',
            'token' => 'required',
        ]);
        if ($validator->fails()) {
            return response()->json([
                "status" => 500,
                "message" => $validator->errors()
            ]);
        }
        $datatoken = MetaData::select('meta_value')->where("active", 1)->where("meta_module", 'branch')->where("meta_key", 'branch_token')->first();

        $token_for_db = (int)$datatoken->meta_value;
        $token_for_required = (int)$request->input('token');

        if ($token_for_db == $token_for_required) {
            $userprofile = $request->input('userprofile');
            if ($userprofile) {
                $path = 'public/files/';
                $path .= $userprofile;
                $path = $request->file('file')->store($path);
                return response()->json([
                    "status" => 200,
                    "message" => "Upload image successfully.",
                    "upload_data" => [
                        "url" => url('/') . "/storage" . str_replace("public", "", $path),
                        "full_path" => url('/') . "/storage" . str_replace("public", "", $path),
                        "path" => $path,
                    ]
                ]);
            } else {
                $file = $request->file('file');
                $name = $request->file('file')->getClientOriginalName();
                $path = $request->file('file')->store('public/files');
                MetaData::where('meta_key', 'store_logo')->update(["meta_value" => url('/') . "/storage" . str_replace("public", "", $path)]);
                return response()->json([
                    "status" => 200,
                    "message" => "Upload image successfully.",
                    "upload_data" => [
                        // "url" => url('/')."/storage".str_replace("public","",$path),
                        "full_path" => url('/') . "/storage" . str_replace("public", "", $path),
                        "path" => $path,
                    ]
                ]);
            }
        } else {
            return response()->json([
                "status" => false,
                "message" => "Token ไม่เหมือนกัน "
            ]);
        }
    }
    public function import()
    {
        DB::beginTransaction();
        $data = Excel::toArray(new ProductImport, request()->file('file'));
        $dataProduct = Product::where('product_id', '!=', 1)->where('product_id', '!=', 2)->where("active", 1)->get();
        collect(head($data))->each(function ($row, $key) {
        });
        foreach ($data as $key => $value) {
            $count = count($value);

            for ($i = 0; $i <  $count; $i++) {
                if (!$value[$i]['product_id']) {
                    $checkDuplicate = DB::table('product')->select('*')->where('product_name', $value[$i]['product_name'])->orWhere('barcode', $value[$i]['barcode'])->where('barcode', '!=', NULL)->where('product_id', '!=', 1)->where('product_id', '!=', 2)->first();

                    if (!$checkDuplicate == null) {
                        DB::rollBack();
                        return response()->json([
                            "success" =>  false,
                            "message" => "สินค้านี้มีชื่อ " . $value[$i]['product_name'] . " หรือบาร์โค้ด " . $value[$i]['barcode'] . " อยู่ในระบบแล้ว",
                        ]);
                    }
                } else {

                    $checkDuplicateProduct = DB::table('product')->select('*')->where('product_name', $value[$i]['product_name'])->where('product_id', '!=', $value[$i]['product_id'])->first();
                    $checkDuplicateProductBarcode = DB::table('product')->select('*')->where('barcode', $value[$i]['barcode'])->where('barcode', '!=', null)->where('product_id', '!=', $value[$i]['product_id'])->first();

                    if (!$checkDuplicateProduct == []) {
                        DB::rollBack();
                        return response()->json([
                            "success" =>  false,
                            "message" => "สินค้ามีชื่อ " . $checkDuplicateProduct->product_name . " อยู่ในระบบแล้ว",
                        ]);
                    }
                    if (!$checkDuplicateProductBarcode == []) {
                        DB::rollBack();
                        return response()->json([
                            "success" =>  false,
                            "message" => "สินค้ามีบาร์โค้ด " . $checkDuplicateProductBarcode->barcode . " อยู่ในระบบแล้ว",
                        ]);
                    }
                }
                $value[$i]["image"] = "public/files/defaultproduct.png";
            }
        }
        DB::table('product')->upsert($value, ["product_id"]);
        DB::commit();
        return response()->json([
            "success" => true,
            "message" => "success','Data Imported Successfully"
        ]);
    }
    public function importStock()
    {
        $user = auth()->user();
        $data = Excel::toArray(new ImportStock, request()->file('file'));
        $id  = Stock_adjustment::insertGetId([
            'stock_adjustment_name' => "Import Stock",
            'create_datetime' =>  $this->datetimecurrent(),
            'user_id' =>  $user->id,
            'type' => 1,
        ]);
        $data = collect(head($data))->each(function ($row, $key) {
        });
        foreach ($data as $key => $value) {
            if (!$key == 0) {
                $this->stock($value[0], $value[1], $id);
            }
        }
        return response()->json([
            "success" => true,
            "message" => "success','Data Imported Successfully"
        ]);
    }
    private function stock($barcode, $qty, $id)
    {
        DB::beginTransaction();
        $obj = array();
        $data = Product::select("product_id", "stock_qty")->where("barcode", $barcode)->first();
        if (!$data == []) {
            $obj["stock_adjustment_id"] = $id;
            $obj["create_datetime"] = $this->datetimecurrent();
            $obj["product_id"] = $data->product_id;
            $obj["computed_qty"] = $data->stock_qty;
            $obj["real_qty"] = $qty;
            $obj["different_qty"] = $data->stock_qty - $qty;
            Stock_adjustment_line::insert($obj);
            Product::where('product_id', $obj["product_id"])->update([
                'stock_qty' => $obj["real_qty"]
            ]);
            $dataStockMove = [
                'product_id' => $obj["product_id"],
                'ref_type' => 'App\Models\Stock_adjustment',
                'ref_id' => $obj["stock_adjustment_id"],
                'qty' => $obj["different_qty"],
                'create_datetime' => $obj["create_datetime"]
            ];
            Stock_move::insert($dataStockMove);
            DB::commit();
        } else {
            DB::rollback();
            return response()->json([
                "success" => false,
                "message" => "Barcode not found in product",
            ]);
        }
    }
}
