<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Http\Resources\PurchaseOrderResource;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Arr;
use Illuminate\Http\Request;
use App\Models\PurchaseOrder;
use App\Models\Product;
use App\Models\Stock_move;
use Illuminate\Support\Facades\Validator;

class PurchaseOrderController extends Controller
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
        $total = 0;
        $purchase = PurchaseOrderResource::collection(PurchaseOrder::all()->sortByDesc('create_datetime'));
        foreach ($purchase as  $data) {
            if ($data->active == 1) {
                $total += $data->total;
            }
        }
        return response()->json([
            "success" => true,
            "message" => "Purchase Order List.",
            "data" => $purchase,
            "total" => $total
        ]);
    }
    public function showbyid($id)
    {

        $purchase = PurchaseOrderResource::collection(PurchaseOrder::all()->where("active", 1)->where("purchase_order_id", $id));

        if (!$purchase->isEmpty()) {

            return response()->json([
                "success" => true,
                "message" => "Purchase Order found.",
                "data" => $purchase
            ]);
        } else {
            return response()->json([
                "success" => FALSE,
                "message" => "Purchase Order Not found.",
            ]);
        }
    }
    public function GenPuchaseID()
    {
        $yearcurrent = Carbon::now()->timezone('Asia/Bangkok')->format("Y");
        $Puchase_ID = '';
        $count = DB::table('purchase_order')->select('*')->count();
        $count = $count + 1;
        $Puchase_ID .= "PO/";
        $Puchase_ID .= $yearcurrent .= "/";
        $Puchase_ID .= $count;
        return response()->json([
            "success" => true,
            "message" => $Puchase_ID,
        ]);
    }
    public function addedit(Request $request)
    {
        DB::beginTransaction();

        $jsonarray = json_decode(json_encode($request->input("purchase_order_line")), TRUE);
        $datetimecurrent = $this->datetimecurrent();
        $user = auth()->user();
        $validator = Validator::make($request->all(), [
            'purchase_order_number' => 'required|string|between:2,100',
            // 'supplier_id' => 'required',
            // 'comment' => 'required|string|between:2,100',
        ]);
        if ($validator->fails()) {
            return response()->json([
                "status" => false,
                "messageError" => $validator->errors()->toJson()
            ], 400);
        }
        $data = ($request->input());
        $data = Arr::except($data, ['purchase_order_line']);
        $data["status"] = "0";
        $dataPurchaseOrderLine = [];

        if ($request->input('purchase_order_id')) {
            PurchaseOrder::where('purchase_order_id', $request->input('purchase_order_id'))->update($data);
            if ($request->input("purchase_order_line")) {

                foreach ($jsonarray as $data) {
                    if (isset($data["purchase_order_line_id"])) {

                        $obj["product_id"] = $data["product_id"];
                        $obj["purchase_order_id"] = $request->input('purchase_order_id');
                        $obj["purchase_order_line_id"] = $data["purchase_order_line_id"];
                        $obj["product_uom_id"] = $data["product_uom_id"];
                        $obj["qty"] =  $data["qty"];
                        $obj["price"] = $data["price"];
                        $obj["active"] =  $data["active"];
                        array_push($dataPurchaseOrderLine, $obj);
                    } else {
                        $obj["product_id"] = $data["product_id"];
                        $obj["purchase_order_id"] = $request->input('purchase_order_id');
                        $obj["purchase_order_line_id"] = null;
                        $obj["product_uom_id"] = $data["product_uom_id"];
                        $obj["qty"] =  $data["qty"];
                        $obj["price"] = $data["price"];
                        $obj["active"] =  $data["active"];
                        array_push($dataPurchaseOrderLine, $obj);
                    }
                }

                DB::table('purchase_order_line')->upsert($dataPurchaseOrderLine, ["purchase_order_line_id", "product_id", "product_uom_id", "purchase_order_id"]);

                $total = 0;
                foreach ($jsonarray as $data) {
                    if ($data["active"] == 1) {
                        $sum = $data["price"] * $data["qty"];
                        $total += $sum;
                    }
                }
                PurchaseOrder::where('purchase_order_id', $request->input('purchase_order_id'),)->update([
                    'subtotal' => $total,
                    "total" => $total
                ]);
                DB::commit();
                return response()->json([
                    "success" => true,
                    "message" => "Purchase Order created successfully.",
                ]);
            } else {
                DB::rollback();
                return response()->json([
                    "success" => true,
                    "message" => "Purchase Order created fail.",
                ]);
            }
        } else {
            $id  = PurchaseOrder::insertGetId([
                'purchase_order_number' => $request->input('purchase_order_number'),
                'supplier_id' => $request->input('supplier_id'),
                'comment' => $request->input('comment'),
                'status' => "0",
                'create_datetime' => $request->input('create_datetime'),
                'user_id' => $user->id
            ]);


            if ($request->input("purchase_order_line")) {
                $total = 0;
                foreach ($jsonarray as $data) {
                    if (isset($data["purchase_order_line_id"])) {

                        $obj["product_id"] = $data["product_id"];
                        $obj["purchase_order_id"] = $id;
                        $obj["purchase_order_line_id"] = $data["purchase_order_line_id"];
                        $obj["product_uom_id"] = $data["product_uom_id"];
                        $obj["qty"] =  $data["qty"];
                        $obj["price"] = $data["price"];
                        $obj["active"] =  $data["active"];
                        array_push($dataPurchaseOrderLine, $obj);
                    } else {
                        $obj["product_id"] = $data["product_id"];
                        $obj["purchase_order_id"] = $id;
                        $obj["purchase_order_line_id"] = null;
                        $obj["product_uom_id"] = $data["product_uom_id"];
                        $obj["qty"] =  $data["qty"];
                        $obj["price"] = $data["price"];
                        $obj["active"] =  $data["active"];
                        array_push($dataPurchaseOrderLine, $obj);
                    }
                    if ($data["active"] == 1) {
                        $sum = $data["price"] * $data["qty"];
                        $total += $sum;
                    }
                }
                DB::table('purchase_order_line')->upsert($dataPurchaseOrderLine, ["purchase_order_line_id", "product_id", "product_uom_id", "purchase_order_id"]);

                PurchaseOrder::where('purchase_order_id', $id)->update([
                    'subtotal' => $total,
                    "total" => $total
                ]);


                DB::commit();

                return response()->json([
                    "success" => true,
                    "message" => "Purchase Order created successfully.",
                ]);
            } else {
                DB::rollback();
                return response()->json([
                    "success" => true,
                    "message" => "Purchase Order created fail.",
                ]);
            }
        }
    }
    public function save(Request $request, $id)
    {
        DB::beginTransaction();

        $jsonarray = json_decode(json_encode($request->input("purchase_order_line")), TRUE);
        $datetimecurrent = $this->datetimecurrent();
        $user = auth()->user();
        $validator = Validator::make($request->all(), [
            'purchase_order_number' => 'required|string|between:2,100',
            'purchase_order_line' => 'required',
        ]);
        if ($validator->fails()) {
            return response()->json([
                "status" => false,
                "messageError" => $validator->errors()->toJson()
            ], 400);
        }
        $data = ($request->input());
        $data = Arr::except($data, ['purchase_order_line']);
        $data["status"] = "1";
        $dataPurchaseOrderLine = [];
        $dataStockMove = [];
        PurchaseOrder::where('purchase_order_id', $id)->update($data);
        if ($request->input("purchase_order_line")) {

            foreach ($jsonarray as $data) {
                if (isset($data["purchase_order_line_id"])) {

                    $obj["product_id"] = $data["product_id"];
                    $obj["purchase_order_id"] = $id;
                    $obj["purchase_order_line_id"] = $data["purchase_order_line_id"];
                    $obj["product_uom_id"] = $data["product_uom_id"];
                    $obj["qty"] =  $data["qty"];
                    $obj["price"] = $data["price"];
                    $obj["active"] =  $data["active"];
                    array_push($dataPurchaseOrderLine, $obj);
                } else {
                    $obj["product_id"] = $data["product_id"];
                    $obj["purchase_order_id"] = $id;
                    $obj["purchase_order_line_id"] = null;
                    $obj["product_uom_id"] = $data["product_uom_id"];
                    $obj["qty"] =  $data["qty"];
                    $obj["price"] = $data["price"];
                    $obj["active"] =  $data["active"];
                    array_push($dataPurchaseOrderLine, $obj);
                }
            }
            DB::table('purchase_order_line')->upsert($dataPurchaseOrderLine, ["purchase_order_line_id", "product_id", "purchase_order_id"]);

            DB::commit();

            $total = null;
            foreach ($jsonarray as $data) {
                if ($data["active"] == 1) {
                    $sum = $data["price"] * $data["qty"];
                    $total += $sum;
                }
            }
            PurchaseOrder::where('purchase_order_id', $request->input('purchase_order_id'),)->update([
                'subtotal' => $total,
                "total" => $total
            ]);
            DB::commit();

            foreach ($jsonarray as $data) {
                if (!$data["product_uom_id"] == []) {
                    $dataProduct =  DB::table('product')->select('*')->where('active', 1)->where('product_id', $data["product_id"])->first();
                    $dataProductUOM =  DB::table('product_uom')->select('multiple_qty')->where('active', 1)->where('product_uom_id', $data["product_uom_id"])->first();
                    $sumAvgUom = ($data["price"] / $dataProductUOM->multiple_qty);

                    $sumPrice = ($sumAvgUom * ($data["qty"] * $dataProductUOM->multiple_qty)) + ($dataProduct->current_average_cost * $dataProduct->stock_qty);
                    $sumQty = ($data["qty"] * $dataProductUOM->multiple_qty) + $dataProduct->stock_qty;
                    $resultQty = ($data["qty"] * $dataProductUOM->multiple_qty);
                    if ($sumQty == 0 || $dataProduct->stock_qty < 0) {
                        $current_average_cost = $sumPrice;
                    } else {
                        $current_average_cost = $sumPrice / $sumQty;
                    }
                    if ($dataProduct->stock_qty < 0) {
                        $current_average_cost =  $sumAvgUom;
                    }
                    Product::where('product_id', $data["product_id"],)->update([
                        'stock_qty' => $sumQty,
                        "current_average_cost" => $current_average_cost
                    ]);
                    $obj = [
                        'product_id' => $data["product_id"],
                        'ref_type' => 'App\Models\PurchaseOrder',
                        'ref_id' => $id,
                        'qty' => $resultQty,
                        'create_datetime' => $datetimecurrent
                    ];
                    array_push($dataStockMove, $obj);
                } else {
                    $dataProduct =  DB::table('product')->select('*')->where('active', 1)->where('product_id', $data["product_id"])->first();
                    $sumPrice = ($data["price"] * $data["qty"]) + ($dataProduct->current_average_cost * $dataProduct->stock_qty);
                    $sumQty = ($data["qty"] + $dataProduct->stock_qty);
                    if ($sumQty == 0) {
                        $current_average_cost = $sumPrice;
                    } else {
                        $current_average_cost = $sumPrice / $sumQty;
                    }
                    if ($dataProduct->stock_qty < 0) {
                        $current_average_cost =  $data["price"];
                    }
                    Product::where('product_id', $data["product_id"],)->update([
                        'stock_qty' => $sumQty,
                        "current_average_cost" => $current_average_cost
                    ]);
                    $obj = [
                        'product_id' => $data["product_id"],
                        'ref_type' => 'App\Models\PurchaseOrder',
                        'ref_id' => $id,
                        'qty' => $data["qty"],
                        'create_datetime' => $datetimecurrent
                    ];
                    array_push($dataStockMove, $obj);
                }
            }
            Stock_move::insert($dataStockMove);
            DB::commit();

            return response()->json([
                "success" => true,
                "message" => "Purchase Order save successfully.",
            ]);
        } else {
            DB::rollback();
            return response()->json([
                "success" => true,
                "message" => "Purchase Order save fail.",
            ]);
        }
    }

    public function cancel(Request $request, $id)
    {
        DB::beginTransaction();

        $jsonarray = json_decode(json_encode($request->input("purchase_order_line")), TRUE);
        $datetimecurrent = $this->datetimecurrent();
        $user = auth()->user();
        $validator = Validator::make($request->all(), [
            'purchase_order_number' => 'required|string|between:2,100',
            'purchase_order_line' => 'required',
        ]);
        if ($validator->fails()) {
            return response()->json([
                "status" => false,
                "messageError" => $validator->errors()->toJson()
            ], 400);
        }
        $data = ($request->input());
        $data = Arr::except($data, ['purchase_order_line']);
        $data["status"] = "2";
        $dataStockMove = [];
        PurchaseOrder::where('purchase_order_id', $id)->update($data);
        if ($request->input("purchase_order_line")) {
            foreach ($jsonarray as $data) {
                $data["purchase_order_id"] = $id;
                DB::table('purchase_order_line')->upsert($data, ["purchase_order_line_id", "product_id", "purchase_order_id"]);
            }
            DB::commit();

            $total = null;
            foreach ($jsonarray as $data) {
                if ($data["active"] == 1) {
                    $sum = $data["price"] * $data["qty"];
                    $total += $sum;
                    $total * (-1);
                }
                PurchaseOrder::where('purchase_order_id', $request->input('purchase_order_id'),)->update([
                    'subtotal' => $total * (-1),
                    'active' => 0,
                    'subtotal' => $total * (-1),
                    "total" => $total * (-1)
                ]);
            }
            DB::commit();

            foreach ($jsonarray as $data) {
                if (!$data["product_uom_id"] == []) {
                    $dataProduct =  DB::table('product')->select('*')->where('active', 1)->where('product_id', $data["product_id"])->first();
                    $dataProductUOM =  DB::table('product_uom')->select('multiple_qty')->where('active', 1)->where('product_uom_id', $data["product_uom_id"])->first();
                    $sumAvgUom = (($data["price"] * (-1)) / $dataProductUOM->multiple_qty);
                    $sumPrice = ($dataProduct->current_average_cost * $dataProduct->stock_qty) + ($sumAvgUom * ($data["qty"] * $dataProductUOM->multiple_qty));
                    $sumQty = $dataProduct->stock_qty - ($data["qty"] * $dataProductUOM->multiple_qty);
                    $resultQty = ($data["qty"] * $dataProductUOM->multiple_qty);
                    if ($sumQty == 0) {
                        $current_average_cost = $sumPrice;
                    } else {
                        $current_average_cost = $sumPrice / $sumQty;
                    }
                    Product::where('product_id', $data["product_id"],)->update([
                        'stock_qty' => $sumQty,
                        "current_average_cost" => $current_average_cost
                    ]);
                    $obj = [
                        'product_id' => $data["product_id"],
                        'ref_type' => 'App\Models\PurchaseOrder',
                        'ref_id' => $id,
                        'qty' => $resultQty,
                        'create_datetime' => $datetimecurrent
                    ];
                    array_push($dataStockMove, $obj);
                } else {
                    $dataProduct =  DB::table('product')->select('*')->where('active', 1)->where('product_id', $data["product_id"])->first();
                    $sumPrice =  ($dataProduct->current_average_cost * $dataProduct->stock_qty) + (($data["price"] * (-1)) * $data["qty"]);
                    $sumQty = ($dataProduct->stock_qty - $data["qty"]);
                    if ($sumQty == 0) {
                        $current_average_cost = $sumPrice;
                    } else {
                        $current_average_cost = $sumPrice / $sumQty;
                    }
                    Product::where('product_id', $data["product_id"],)->update([
                        'stock_qty' => $sumQty,
                        "current_average_cost" => $current_average_cost
                    ]);
                    $obj = [
                        'product_id' => $data["product_id"],
                        'ref_type' => 'App\Models\PurchaseOrder',
                        'ref_id' => $id,
                        'qty' => $data["qty"],
                        'create_datetime' => $datetimecurrent
                    ];
                    array_push($dataStockMove, $obj);
                }
            }
            Stock_move::insert($dataStockMove);
            DB::commit();
            return response()->json([
                "success" => true,
                "message" => "Purchase Order Cancel successfully.",
            ]);
        } else {
            DB::rollback();
            return response()->json([
                "success" => true,
                "message" => "Purchase Order Cancel fail.",
            ]);
        }
    }
}
