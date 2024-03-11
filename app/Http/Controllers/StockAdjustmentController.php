<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\MetaData;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;
use App\Models\Stock_adjustment;
use App\Models\Stock_adjustment_line;
use App\Models\Product;
use App\Models\Stock_move;
use Illuminate\Support\Arr;

class StockAdjustmentController extends Controller
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
            Stock_adjustment::with('user')->where('active', 1)->orderBy('create_datetime', 'DESC')->get();

        return response()->json([
            "success" => true,
            "message" => "Stock List",
            "data" => $data
        ]);
    }
    public function showdate(Request $request)
    {
        $startDate = $request->input('startDate');
        $startDate .= ' 00:00:00';
        $endDate = $request->input('endDate');
        $endDate .= ' 23:59:59';

        $data =
            Stock_adjustment::with('user')->where('active', 1)->where('create_datetime',  '>=', $startDate)
            ->where('create_datetime', '<=', $endDate)->orderBy('create_datetime', 'DESC')->get();

        return response()->json([
            "success" => true,
            "message" => "Stock List",
            "data" => $data
        ]);
    }
    public function showbyid($id)
    {
        $data =
            Stock_adjustment::with('user')->with('stock_adjustment_line')->where('active', 1)->where('stock_adjustment_id', $id)->get();

        return response()->json([
            "success" => true,
            "message" => "Stock List",
            "data" => $data
        ]);
    }
    public function addedit(Request $request)
    {
        DB::beginTransaction();
        $created_datetime = $this->datetimecurrent(); //datatimecurrent

        $jsonarray = json_decode(json_encode($request->input("stock_adjustment_line")), TRUE);
        $datetimecurrent = $this->datetimecurrent();
        $user = auth()->user();
        $validator = Validator::make($request->all(), [
            'stock_adjustment_name' => 'required',
            'stock_adjustment_line' => 'required',
        ]);
        if ($validator->fails()) {
            return response()->json([
                "status" => false,
                "messageError" => $validator->errors()->toJson()
            ], 400);
        }
        $data = ($request->input());
        $data = Arr::except($data, ['stock_adjustment_line']);
        $data["user_id"] =  $user->id;
        $data["create_datetime"] = $created_datetime;
        $data["type"] = 0;
        $new = array();

        if ($request->input('stock_adjustment_id')) {
            Stock_adjustment::where('stock_adjustment_id', $request->input('stock_adjustment_id'))->update($data);
            if ($request->input("stock_adjustment_line")) {

                foreach ($jsonarray as $data) {

                    $obj["stock_adjustment_id"] = $request->input('stock_adjustment_id');
                    $obj["stock_adjustment_line_id"] = $data["stock_adjustment_line_id"];
                    $obj["create_datetime"] = $created_datetime;
                    $obj["product_id"] = $data["product_id"];
                    $obj["computed_qty"] = $data["computed_qty"];
                    $obj["real_qty"] = $data["real_qty"];
                    $obj["different_qty"] = $data["computed_qty"] - $data["real_qty"];
                    array_push($new, $obj);
                }
                DB::table('stock_adjustment_line')->upsert($new, ["stock_adjustment_line_id", "product_id", "stock_adjustment_id"]);

                DB::commit();
                return response()->json([
                    "success" => true,
                    "message" => "stock_adjustment created successfully.",
                ]);
            } else {
                DB::rollback();
                return response()->json([
                    "success" => true,
                    "message" => "stock_adjustment created fail.",
                ]);
            }
        } else {
            // var_dump($data);
            // die;
            $id  = Stock_adjustment::insertGetId($data);


            if ($request->input("stock_adjustment_line")) {

                foreach ($jsonarray as $data) {
                    $obj["stock_adjustment_id"] = $id;
                    $obj["stock_adjustment_line_id"] = $data["stock_adjustment_line_id"];
                    $obj["create_datetime"] = $created_datetime;
                    $obj["product_id"] = $data["product_id"];
                    $obj["computed_qty"] = $data["computed_qty"];
                    $obj["real_qty"] = $data["real_qty"];
                    $obj["different_qty"] = $data["computed_qty"] - $data["real_qty"];
                    array_push($new, $obj);
                }
                DB::table('stock_adjustment_line')->upsert($new, ["stock_adjustment_line_id", "product_id", "stock_adjustment_id"]);

                DB::commit();

                return response()->json([
                    "success" => true,
                    "message" => "stock_adjustment created successfully.",
                ]);
            } else {
                DB::rollback();
                return response()->json([
                    "success" => true,
                    "message" => "stock_adjustment created fail.",
                ]);
            }
        }
    }
    public function save(Request $request)
    {
        DB::beginTransaction();
        $created_datetime = $this->datetimecurrent(); //datatimecurrent
        $jsonarray = json_decode(json_encode($request->input("stock_adjustment_line")), TRUE);
        $user = auth()->user();
        $validator = Validator::make($request->all(), [
            'stock_adjustment_name' => 'required',
            'stock_adjustment_line' => 'required',
        ]);
        if ($validator->fails()) {
            return response()->json([
                "status" => false,
                "messageError" => $validator->errors()->toJson()
            ], 400);
        }
        $data = ($request->input());
        $data = Arr::except($data, ['stock_adjustment_line']);
        $data["user_id"] =  $user->id;
        $data["create_datetime"] = $created_datetime;
        $data["type"] = 1;
        $new = array();
        $dataStockMove = [];

        if ($request->input('stock_adjustment_id')) {
            Stock_adjustment::where('stock_adjustment_id', $request->input('stock_adjustment_id'))->update($data);
            if ($request->input("stock_adjustment_line")) {

                foreach ($jsonarray as $data) {

                    $obj["stock_adjustment_id"] = $request->input('stock_adjustment_id');
                    $obj["create_datetime"] = $created_datetime;
                    $obj["stock_adjustment_line_id"] = $data["stock_adjustment_line_id"];
                    $obj["product_id"] = $data["product_id"];
                    $obj["computed_qty"] = $data["computed_qty"];
                    $obj["real_qty"] = $data["real_qty"];
                    $obj["different_qty"] = $data["computed_qty"] - $data["real_qty"];
                    array_push($new, $obj);

                    $dataqty = $obj["different_qty"];
                    $obj2 = [
                        'product_id' => $obj["product_id"],
                        'ref_type' => 'App\Models\Stock_adjustment',
                        'ref_id' => $obj["stock_adjustment_id"],
                        'qty' => $dataqty,
                        'create_datetime' => $created_datetime
                    ];
                    array_push($dataStockMove, $obj2);
                }
                DB::table('stock_adjustment_line')->upsert($new, ["stock_adjustment_line_id", "product_id", "stock_adjustment_id"]);
                //upddate join table
                DB::statement('update product p LEFT JOIN stock_adjustment_line s ON p.product_id = s.product_id
                set stock_qty = s.real_qty
                WHERE stock_adjustment_id = ' . $request->input('stock_adjustment_id') . '');
                Stock_move::insert($dataStockMove);

                DB::commit();
                return response()->json([
                    "success" => true,
                    "message" => "stock adjustment created successfully.",
                ]);
            } else {
                DB::rollback();
                return response()->json([
                    "success" => true,
                    "message" => "stock adjustment created fail.",
                ]);
            }
        } else {
            $id  = Stock_adjustment::insertGetId($data);


            if ($request->input("stock_adjustment_line")) {

                foreach ($jsonarray as $data) {
                    $obj["stock_adjustment_id"] = $id;
                    $obj["create_datetime"] = $created_datetime;
                    $obj["stock_adjustment_line_id"] = $data["stock_adjustment_line_id"];
                    $obj["product_id"] = $data["product_id"];
                    $obj["computed_qty"] = $data["computed_qty"];
                    $obj["real_qty"] = $data["real_qty"];
                    $obj["different_qty"] = $data["computed_qty"] - $data["real_qty"];
                    array_push($new, $obj);
                    $qty = $obj["different_qty"] * (-1);
                    $obj2 = [
                        'product_id' => $obj["product_id"],
                        'ref_type' => 'App\Models\Stock_adjustment',
                        'ref_id' => $id,
                        'qty' => $qty,
                        'create_datetime' => $created_datetime
                    ];
                    array_push($dataStockMove, $obj2);
                }
                DB::table('stock_adjustment_line')->upsert($new, ["stock_adjustment_line_id", "product_id", "stock_adjustment_id"]);
                DB::statement('update product p LEFT JOIN stock_adjustment_line s ON p.product_id = s.product_id
                set stock_qty = s.real_qty
                WHERE stock_adjustment_id = ' . $id . '');
                Stock_move::insert($dataStockMove);

                DB::commit();

                return response()->json([
                    "success" => true,
                    "message" => "stock adjustment created successfully.",
                ]);
            } else {
                DB::rollback();
                return response()->json([
                    "success" => true,
                    "message" => "stock adjustment created fail.",
                ]);
            }
        }
    }
    public function upStock(Request $request)
    {
        $datatoken = MetaData::select('meta_value')->where("active", 1)->where("meta_module", 'branch')->where("meta_key", 'branch_token')->first();
        $token_for_db = (int)$datatoken->meta_value;
        $token_for_required = (int)$request->input('token');
        // error_log($request->input('meta_key'));
        if ($token_for_db == $token_for_required) {
            $datetimecurrent = $this->datetimecurrent();
            $jsonarray = json_decode(json_encode($request->input("transfer_product_line")), TRUE);
            foreach ($jsonarray as $data) {
                if (!$data["product_uom_id"] == []) {
                    $dataProduct =  DB::table('product')->select('*')->where('active', 1)->where('product_id', $data["product_id"])->first();
                    $dataProductUOM =  DB::table('product_uom')->select('multiple_qty')->where('active', 1)->where('product_uom_id', $data["product_uom_id"])->first();
                    $sumAvgUom = ($data["price"] / $dataProductUOM->multiple_qty);
                    $sumQty = ($data["qty"] * $dataProductUOM->multiple_qty) + $dataProduct->stock_qty;
                    Product::where('product_id', $data["product_id"],)->update([
                        'stock_qty' => $sumQty,
                        "current_average_cost" => $sumAvgUom
                    ]);
                } else {
                    $dataProduct =  DB::table('product')->select('*')->where('active', 1)->where('product_id', $data["product_id"])->first();
                    $sumPrice = ($data["price"] * $data["qty"]) + ($dataProduct->current_average_cost * $dataProduct->stock_qty);
                    $sumQty = ($data["qty"] + $dataProduct->stock_qty);

                    Product::where('product_id', $data["product_id"],)->update([
                        'stock_qty' => $sumQty,
                        "current_average_cost" => $data["price"]
                    ]);
                }
            }
        } else {
            return response()->json([
                "status" => false,
                "message" => "Token ไม่เหมือนกัน "
            ], 401);
        }
    }
    public function downStock(Request $request)
    {
        $datatoken = MetaData::select('meta_value')->where("active", 1)->where("meta_module", 'branch')->where("meta_key", 'branch_token')->first();
        $token_for_db = (int)$datatoken->meta_value;
        $token_for_required = (int)$request->input('token');
        // error_log((int)$request->input('token'));
        if ($token_for_db == $token_for_required) {
            $datetimecurrent = $this->datetimecurrent();
            $jsonarray = json_decode(json_encode($request->input("transfer_product_line")), TRUE);
            foreach ($jsonarray as $data) {
                if (!$data["product_uom_id"] == []) {
                    $dataProduct =  DB::table('product')->select('*')->where('active', 1)->where('product_id', $data["product_id"])->first();
                    $dataProductUOM =  DB::table('product_uom')->select('multiple_qty')->where('active', 1)->where('product_uom_id', $data["product_uom_id"])->first();
                    $sumAvgUom = ($data["price"] / $dataProductUOM->multiple_qty);
                    $sumQty = $dataProduct->stock_qty - ($data["qty"] * $dataProductUOM->multiple_qty);
                    Product::where('product_id', $data["product_id"],)->update([
                        'stock_qty' => $sumQty,
                        "current_average_cost" => $sumAvgUom
                    ]);
                } else {
                    $dataProduct =  DB::table('product')->select('*')->where('active', 1)->where('product_id', $data["product_id"])->first();
                    $sumQty = ($dataProduct->stock_qty - ($data["qty"] * (-1)));

                    Product::where('product_id', $data["product_id"],)->update([
                        'stock_qty' => $sumQty,
                        "current_average_cost" => $data["price"]
                    ]);
                }
            }
        } else {
            return response()->json([
                "status" => false,
                "message" => "Token ไม่เหมือนกัน "
            ], 401);
        }
    }
    public function stock()
    {
        $stock = Product::select('product_id', 'product_name', 'stock_qty', 'current_average_cost')->where("active", 1)->where("type", 1)->get();
        $datanew = array();
        $i = 1;
        $total = 0;
        foreach ($stock as $data) {
            $obj["index"] = $i++;
            $obj["product_id"] = $data->product_id;
            $obj["product_name"] = $data->product_name;
            $obj["stock_qty"] = $data->stock_qty;
            $obj["current_average_cost"] = $data->current_average_cost;
            $obj["product_result"] = floor(($data->current_average_cost * $data->stock_qty) * 100) / 100;
            $total += ($data->current_average_cost * $data->stock_qty);
            array_push($datanew, $obj);
        }

        return response()->json([
            "success" => true,
            "message" => "Product List",
            "data" => $datanew,
            "total" => $total

        ]);
    }
    public function stockName(Request $request)
    {

        $product_id1 = $request->input('product_id1');
        $product_id2 = $request->input('product_id2');
        $product_id3 = $request->input('product_id3');
        $product_id4 = $request->input('product_id4');
        $product_id5 = $request->input('product_id5');
        $product_id6 = $request->input('product_id6');
        $product_id7 = $request->input('product_id7');
        $product_id8 = $request->input('product_id8');
        $product_id9 = $request->input('product_id9');
        $product_id10 = $request->input('product_id10');
        $stock = Product::select('product_id', 'product_name', 'stock_qty', 'current_average_cost')->where("active", 1)->where("type", 1)
            ->where('product_id',  $product_id1)
            ->orwhere('product_id',  $product_id2)
            ->orwhere('product_id',  $product_id3)
            ->orwhere('product_id',  $product_id4)
            ->orwhere('product_id',  $product_id5)
            ->orwhere('product_id',  $product_id6)
            ->orwhere('product_id',  $product_id7)
            ->orwhere('product_id',  $product_id8)
            ->orwhere('product_id',  $product_id9)
            ->orwhere('product_id',  $product_id10)
            ->get();
        $datanew = array();
        $i = 1;
        $total = 0;
        foreach ($stock as $data) {
            $obj["product_id"] = $data->product_id;
            $obj["product_name"] = $data->product_name;
            $obj["stock_qty"] = $data->stock_qty;
            $obj["current_average_cost"] = $data->current_average_cost;
            $obj["product_result"] = floor(($data->current_average_cost * $data->stock_qty) * 100) / 100;
            $total += ($data->current_average_cost * $data->stock_qty);
            array_push($datanew, $obj);
        }

        return response()->json([
            "success" => true,
            "message" => "Product List",
            "data" => $datanew,
            "total" => $total

        ]);
    }
}
