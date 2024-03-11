<?php

namespace App\Http\Controllers;

use App\Http\Resources\OrderReources;
use App\Models\Order_lines;
use App\Models\Orders;
use App\Models\Member;
use App\Models\Order_payment;
use App\Models\PosSession;
use App\Models\Product;
use App\Models\Product_uom;
use App\Models\Stock_move;
use App\Models\MetaData;
use App\Models\Promotion;
use Carbon\Carbon;
// use App\Http\Resources\PurchaseOrderResource;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Arr;
use Illuminate\Http\Request;
use App\Models\PurchaseOrder;
use Illuminate\Support\Facades\Validator;
use DateTime;
use DateTimeZone;
use Memcache;

class OrderController extends Controller
{
    private function datetimecurrent()
    {
        $dt = Carbon::now()->timezone('Asia/Bangkok');
        $toDay = $dt->format('d');
        $toMonth = $dt->format('m');
        $toYear = $dt->format('Y');
        $dateUTC = Carbon::createFromDate($toYear, $toMonth, $toDay, 'UTC');
        $datetimecurrent = Carbon::createFromDate($toYear, $toMonth, $toDay, 'Asia/Bangkok');
        // $difference = $dateUTC->diffInHours($datePST);
        // $datetimecurrent = $dt->addHours($difference);
        return $datetimecurrent;
    }
    private function orderNumberCode()
    {
        $yeardatecurrent = Carbon::now()->timezone('Asia/Bangkok')->format("Y-m");
        $year = Carbon::now()->timezone('Asia/Bangkok')->format("Y");
        $month = Carbon::now()->timezone('Asia/Bangkok')->format("m");
        $new = array();

        $count = DB::table('orders')->select('*')->where('created_datetime', 'LIKE', "%$yeardatecurrent%")->count();
        $datetimecurrent = Carbon::now()->timezone('Asia/Bangkok')->format("Y-m-d H:m:s");
        $order_number_code = '#';
        $count = $count + 1;
        $order_number_code .= $year .= "/";
        $order_number_code  .= $month .= "/";
        $order_number_code .= $count;
        return $order_number_code;
    }
    private function session_receipt_number()
    {
        $user = auth()->user();
        $possession_id = PosSession::select('pos_session_id')->where('user_id', $user->id)->where('close_datetime', NULL)->where('close_cash_amount', NULL)->where('active', 1)->first();
        $session_number = DB::table('orders')
            ->where('pos_session_id', $possession_id->pos_session_id)
            ->count();
        $dataPrefix = MetaData::select('meta_value')->where("active", 1)->where("meta_module", 'printer')->where("meta_key", 'prefix_receipt')->first();
        $session_receipt_number = $dataPrefix->meta_value .= "/";
        $count = $session_number + 1;
        $session_receipt_number .= $possession_id->pos_session_id .= "/";
        $session_receipt_number .= $count;
        return $session_receipt_number;
    }
    private function year_receipt_number()
    {
        $yeardatecurrent = Carbon::now()->timezone('Asia/Bangkok')->format("Y");
        $year = Carbon::now()->timezone('Asia/Bangkok')->format("Y");
        $dataPrefix = MetaData::select('meta_value')->where("active", 1)->where("meta_module", 'printer')->where("meta_key", 'prefix_receipt')->first();
        $count = DB::table('orders')->select('*')->where('created_datetime', 'LIKE', "%$yeardatecurrent%")->count();
        $year_receipt_number = $dataPrefix->meta_value .= "/";
        $count = $count + 1;
        $year_receipt_number .= $year .= "/";
        $year_receipt_number .= $count;
        return $year_receipt_number;
    }
    private function validatePay(Request $request)
    {

        if (!$request->input("order_lines") == []) {
            $jsonarray = json_decode(json_encode($request->input("order_lines")), TRUE);

            foreach ($jsonarray as $data_order_line) {
                if ($data_order_line["is_edit"] == 1) {
                    return [
                        "success" => true,
                    ];
                } elseif ($data_order_line["is_edit"] == 0) {
                    if ($data_order_line["product_uom_id"] == "") {
                        if ($request->input("pricelist_id") || $request->input("pricelist_id") == "") {
                            $dataPriceList = DB::table('product_pricelist')->select('price')->where('product_id', $data_order_line["product_id"])->where('pricelist_id', $request->input("pricelist_id"))->where('active', 1)->get();
                            $dataPriceProduct = Product::select('price')->where('product_id', $data_order_line["product_id"])->where('active', 1)->get();
                            if ($dataPriceList->count() >= 1) {
                                if ($dataPriceList[0]->price == $data_order_line["price"]) {
                                    return [
                                        "success" => true,
                                    ];
                                } else {
                                    return
                                        [
                                            "success" => FALSE,
                                            "message" => "pricelist is not the same price",
                                        ];
                                }
                            } elseif ($dataPriceProduct[0]->price == $data_order_line["price"]) {
                                return [
                                    "success" => true,
                                ];
                            } else {
                                return
                                    [
                                        "success" => FALSE,
                                        "message" => "price is not the same price",
                                    ];
                            }
                        }
                    }
                    $dataPriceUom = DB::table('product_uom')->select('price')->where('product_uom_id', $data_order_line["product_uom_id"])->where('product_id', $data_order_line["product_id"])->where('active', 1)->get();
                    if ($dataPriceUom->count() >= 1) {
                        if ($dataPriceUom[0]->price == $data_order_line["price"]) {
                            return [
                                "success" => true,
                            ];
                        } else {
                            return
                                [
                                    "success" => FALSE,
                                    "message" => "price is not the same price UOM",
                                ];
                        }
                    }
                    if ($request->input("pricelist_id")) {
                        $dataPriceList = DB::table('product_pricelist')->select('price')->where('product_id', $data_order_line["product_id"])->where('product_pricelist_id', $request->input("pricelist_id"))->where('active', 1)->get();
                        $dataPriceProduct = Product::select('price')->where('product_id', $data_order_line["product_id"])->where('active', 1)->get();
                        if ($dataPriceList[0]->price == $data_order_line["price"]) {
                            return [
                                "success" => true,
                            ];
                        } elseif ($dataPriceProduct[0]->price == $data_order_line["price"]) {
                            return [
                                "success" => true,
                            ];
                        } else {
                            return
                                [
                                    "success" => FALSE,
                                    "message" => "price is not the same price",
                                ];
                        }
                    }
                } else {
                    return
                        [
                            "success" => FALSE,
                            "message" => " don't have is_edit",
                        ];
                }
            }
        } else {
            return [
                "success" => false,
            ];
        }
    }
    public function testmem()
    {

        define('MEMCACHED_HOST', '203.154.91.177');
        define('MEMCACHED_PORT', '11211');
        $mc = new Memcache;
        $cacheAvailable = $mc->connect(MEMCACHED_HOST, MEMCACHED_PORT);
        // error_reporting(E_ALL & ~E_NOTICE);



        // $mc = new Memcached();

        // $mc->addServer("203.154.91.177", 11211);
        $datetimecurrent =
            Carbon::now()->timezone('Asia/Bangkok')->format("Y-m-d");

        $dataPoint = Orders::where("active", 1)->where("created_datetime", $datetimecurrent)->count();


        $mc->set("key", $datetimecurrent);

        $mc->set("bar", "Memcached...");



        $arr = array(

            $mc->get("key"),

            $mc->get("bar")

        );

        var_dump($arr);
        var_dump($dataPoint);
    }

    public function pay(Request $request)
    {

        $validPayment = $this->validatePay($request);

        if ($validPayment["success"] == true) {
            $order_number_code = $this->orderNumberCode();
            $year_receipt_number = $this->year_receipt_number();
            $session_receipt_number = $this->session_receipt_number();

            $datetimecurrent = $this->datetimecurrent();

            DB::beginTransaction();
            $dataPoint = MetaData::select('meta_value')->where("active", 1)->where("meta_module", 'store')->where("meta_key", 'store_point')->first();
            $dataPointInt = (int)$dataPoint["meta_value"];
            $dataVatIncluded = MetaData::select('meta_value')->where("active", 1)->where("meta_module", 'store')->where("meta_key", 'store_vat_include')->first();
            $dataVatIncludedInt = (int)$dataVatIncluded["meta_value"];
            $Datavat = MetaData::select('meta_value')->where("active", 1)->where("meta_module", 'store')->where("meta_key", 'store_vat')->first();
            $vat = (int)$Datavat["meta_value"];
            $jsonarray = json_decode(json_encode($request->input()), TRUE);
            $user = auth()->user();
            $validator = Validator::make($request->all(), [
                'is_vat' => 'required',
                'total_payment' => 'required',
                'order_lines' => 'required',
                'order_payment' => 'required',
            ]);
            if ($validator->fails()) {
                return response()->json([
                    "status" => false,
                    "messageError" => $validator->errors()->toJson()
                ], 400);
            }

            $possession_id = PosSession::select('pos_session_id')->where('user_id', $user->id)->where('close_datetime', NULL)->where('close_cash_amount', NULL)->where('active', 1)->get();
            if ($possession_id->count() == 1) {

                $orderID  = Orders::insertGetId([
                    'order_number' => $order_number_code,
                    'session_receipt_number' => $session_receipt_number,
                    'year_receipt_number' => $year_receipt_number,
                    'subtotal' => 0,
                    'vat' => 0,
                    'total' => 0,
                    'total_payment' => $request->input("total_payment"),
                    'total_recive' => $request->input("total_payment"),
                    'total_margin' => 0,
                    'payment_amount' => $request->input("payment_amount"),
                    'price_change' => 0,
                    'user_id' => $user->id,
                    'member_id' => $request->input("member_id"),
                    'pos_session_id' => $possession_id[0]->pos_session_id,
                    'created_datetime' => $datetimecurrent,
                    'is_vat' => $request->input("is_vat"),
                    'pricelist_id' => $request->input("pricelist_id")
                ]);

                $dataset = [];

                if (!$request->input("order_lines") == []) {
                    $OrderLinejsonarray = json_decode(json_encode($request->input("order_lines")), TRUE);

                    foreach ($OrderLinejsonarray as $data_order_line) {
                        $dataProduct = Product::where('product_id', $data_order_line["product_id"])->where('active', 1)->get();
                        if ($dataProduct[0]->is_vat == 1 &&  $request->input("is_vat") == 1) {
                            $margin = $data_order_line["price"] - $dataProduct[0]->current_average_cost;

                            if ($data_order_line["product_uom_id"]) {
                                $dataProductUom = Product_uom::select("multiple_qty", "price")->where('product_uom_id', $data_order_line["product_uom_id"])->where('active', 1)->first();
                                $price_uom = $dataProductUom->price / $dataProductUom->multiple_qty;
                                $margin = $price_uom - $dataProduct[0]->current_average_cost;
                                $total_margin = $margin * $dataProductUom->multiple_qty;
                                $total_margin = ($margin * $dataProductUom->multiple_qty) * ($data_order_line["qty"]);
                            } else {
                                $total_margin = $margin * $data_order_line["qty"];
                            }

                            if ($data_order_line["product_id"] == 1) {
                                $total_margin = 0;
                                $margin = 0;
                                DB::table('orders')->where('order_id', $orderID)->update(['type' => 2]); //type 2 == ชำระหนี้

                            }

                            if ($dataVatIncludedInt == 0) {
                                $vat_calculate = $data_order_line["price"] * ($vat / 100);
                                $total_vat = $vat_calculate * $data_order_line["qty"];
                                $subtotal = ($data_order_line["price"] * $data_order_line["qty"]);
                                $total = $total_vat + $subtotal;
                            } else if ($dataVatIncludedInt == 1) {
                                $vat_calculate = $data_order_line["price"] * ($vat / (100 + $vat)); /*การคิดVATใน*/
                                $total_vat = $vat_calculate * $data_order_line["qty"];
                                $subtotal = ($data_order_line["price"] * $data_order_line["qty"]) - $total_vat;/*การคิดVATใน*/
                                $total = $total_vat + $subtotal;
                            } else {
                                $vat_calculate = 0;
                                $total_vat = $vat_calculate * $data_order_line["qty"];
                                $subtotal = ($data_order_line["price"] * $data_order_line["qty"]);
                                $total = $total_vat + $subtotal;
                            }
                            // $vat_calculate = $data_order_line["price"] * ($vat / (100 + $vat)); /*การคิดVATใน*/
                            // $vat_calculate = $data_order_line["price"] * ($vat / 100);
                            // $total_vat = $vat_calculate * $data_order_line["qty"];
                            // $total_margin = $margin * $data_order_line["qty"];
                            // $subtotal = ($data_order_line["price"] * $data_order_line["qty"]);
                            // $total = $total_vat + $subtotal;
                            $obj['order_id'] = $orderID;
                            $obj['product_id'] = $data_order_line["product_id"];
                            $obj["product_uom_id"] = $data_order_line["product_uom_id"];
                            $obj["promotion_id"] = $data_order_line["promotion_id"];
                            $obj['qty'] = $data_order_line["qty"];
                            $obj['price'] = $data_order_line["price"];
                            $obj['margin'] = $margin;
                            $obj["vat"] = $vat_calculate;
                            $obj['total_vat'] = $total_vat;
                            $obj['total_margin'] = $total_margin;
                            $obj['subtotal'] = $subtotal;
                            $obj['total'] = $total;
                            $obj['is_edit'] = $data_order_line["is_edit"];
                            $obj['note'] = $data_order_line["note"];
                            array_push($dataset, $obj);
                        } else {
                            $margin = $data_order_line["price"] - $dataProduct[0]->current_average_cost;

                            if ($data_order_line["product_uom_id"]) {
                                $dataProductUom = Product_uom::select("multiple_qty", "price")->where('product_uom_id', $data_order_line["product_uom_id"])->where('active', 1)->first();
                                $price_uom = $dataProductUom->price / $dataProductUom->multiple_qty;
                                $margin = $price_uom - $dataProduct[0]->current_average_cost;
                                $total_margin = $margin * $dataProductUom->multiple_qty;
                                $total_margin = ($margin * $dataProductUom->multiple_qty) * ($data_order_line["qty"]);
                                $margin = $total_margin;
                            } else {
                                $total_margin = $margin * $data_order_line["qty"];
                            }



                            // $total_margin = $margin * $data_order_line["qty"];
                            $subtotal = ($data_order_line["price"] * $data_order_line["qty"]);
                            $total = 0 + $subtotal;
                            $obj['order_id'] = $orderID;
                            $obj['product_id'] = $data_order_line["product_id"];
                            $obj["product_uom_id"] = $data_order_line["product_uom_id"];
                            $obj["promotion_id"] = $data_order_line["promotion_id"];
                            $obj['qty'] = $data_order_line["qty"];
                            $obj['price'] = $data_order_line["price"];
                            $obj['margin'] = $margin;
                            $obj["vat"] = 0;
                            $obj['total_vat'] = 0;
                            $obj['total_margin'] = $total_margin;
                            $obj['subtotal'] = $subtotal;
                            $obj['total'] = $total;
                            $obj['is_edit'] = $data_order_line["is_edit"];
                            $obj['note'] = $data_order_line["note"];
                            array_push($dataset, $obj);
                        }
                    }
                    $order_ine_id = Order_lines::insert($dataset);
                } else {
                    DB::rollBack();
                    return response()->json([
                        "success" => FALSE,
                        "message" => " don't have order_lines",
                    ]);
                }

                $dataOrder = Order_lines::select('*')->where('order_id', $orderID)->where('active', 1)->get();
                $dataOrderpayment = Orders::select('total_payment', 'payment_amount')->where('order_id', $orderID)->where('active', 1)->get();
                $count = Order_lines::select('*')->where('order_id', $orderID)->where('active', 1)->count();
                $jsonarray = json_decode(json_encode($dataOrder), TRUE);
                $subtotal = 0;
                $vat = 0;
                $total = 0;
                $total_margin = 0;
                $sumQty = 0;
                foreach ($jsonarray as $data) {
                    $dataProduct = Product::select("stock_qty")->where('product_id', $data["product_id"])->where('active', 1)->get();
                    $sumSubtotal = $data["subtotal"];
                    $subtotal += $sumSubtotal;
                    $sumVat = $data["total_vat"];
                    $vat += $sumVat;
                    $sumTotal = $data["total"];
                    $total += $sumTotal;
                    $sumTotal_margin = $data["total_margin"];
                    $total_margin += $sumTotal_margin;
                    $sumQty += $data["qty"];
                    $price_change = $dataOrderpayment[0]->total_payment - $dataOrderpayment[0]->payment_amount;
                    $total_stock = $dataProduct[0]->stock_qty - $data["qty"];
                    $total_stock_uom = 0;
                    if (!$data["product_uom_id"] == "") {
                        $dataProductUom = Product_uom::select("multiple_qty")->where('product_uom_id', $data["product_uom_id"])->where('product_id', $data["product_id"])->where('active', 1)->get();
                        $total_stock_uom =  $dataProductUom[0]->multiple_qty * $data["qty"];
                        $total_stock_uom =  $dataProduct[0]->stock_qty - $total_stock_uom;
                        Product::where('product_id', $data["product_id"])->update([
                            'stock_qty' => $total_stock_uom
                        ]);
                    } elseif (!$data["promotion_id"] == "") {
                        $dataPromotion = Promotion::select("qty")->where('promotion_id', $data["promotion_id"])->where('product_id', $data["product_id"])->where('active', 1)->get();
                        $total_stock_promotion =  $dataProduct[0]->stock_qty - $dataPromotion[0]->qty;
                        Product::where('product_id', $data["product_id"])->update([
                            'stock_qty' => $total_stock_promotion
                        ]);
                    } else {
                        Product::where('product_id', $data["product_id"])->update([
                            'stock_qty' => $total_stock
                        ]);
                    }
                }
                Orders::where('order_id', $orderID)->update([
                    'subtotal' => $subtotal,
                    'vat' => $vat,
                    'total' => $total,
                    'total_margin' => $total_margin,
                    "price_change" => $price_change
                ]);
                if (!$request->input("order_payment") == []) {
                    $jsonarray = json_decode(json_encode($request->input("order_payment")), TRUE);
                    $dataDebt = Member::where('member_id',  $request->input("member_id"))->where('active', 1)->get();
                    foreach ($jsonarray as $data_order_payment) {
                        if ($data_order_payment["payment_id"] == 1) {
                            $data = [
                                'order_id' => $orderID,
                                'payment_id' => $data_order_payment["payment_id"],
                                'amount' => $data_order_payment["amount"] - $price_change
                            ];
                        } else {
                            $data = [
                                'order_id' => $orderID,
                                'payment_id' => $data_order_payment["payment_id"],
                                'amount' => $data_order_payment["amount"]
                            ];
                        }


                        if ($data_order_payment["payment_id"] == 2) {
                            $debtCurrent = $dataDebt[0]->debt + $data_order_payment["amount"];
                            Order_payment::insert($data);
                            DB::table('member')->where('member_id', $request->input("member_id"))->update(['debt' => $debtCurrent]);
                        } elseif ($data_order_payment["payment_id"] == 3) {
                            Order_payment::insert($data);
                        } else {
                            Order_payment::insert($data);
                        }
                    }
                    $dataStockMove = [];

                    foreach ($dataOrder as $data) {
                        if ($data["product_id"] == 1) {
                            $debt = $dataDebt[0]->debt - $data["price"];
                            DB::table('member')->where('member_id', $request->input("member_id"))->update(['debt' => $debt]);
                        }
                        $dataqty = $data["qty"] * (-1);
                        $obj = [
                            'product_id' => $data["product_id"],
                            'ref_type' => 'App\Models\Orders',
                            'ref_id' => $data["order_id"],
                            'qty' => $dataqty,
                            'create_datetime' => $datetimecurrent
                        ];
                        array_push($dataStockMove, $obj);
                    }
                    Stock_move::insert($dataStockMove);

                    foreach ($OrderLinejsonarray as $data) {
                        if (!$data["promotion_id"] == [] && !$request->input("member_id") == []) {
                            $dataPointCurrent = Member::select("point")->where('member_id',  $request->input("member_id"))->where('active', 1)->first();
                            $dataPointProduct = Promotion::select("point")->where('promotion_id', $data["promotion_id"])->where('active', 1)->first();
                            if ($dataPointCurrent->point >= $dataPointProduct->point) {
                                $resultPoint = $dataPointCurrent->point - $dataPointProduct->point;
                                Member::where('member_id', $request->input("member_id"))->update(['point' => $resultPoint]);
                            } else {
                                DB::rollBack();
                                return response()->json([
                                    "success" => FALSE,
                                    "message" => " Member not have point Or Not enough points",
                                ]);
                            }
                        }
                    }
                    if ($data_order_line["product_id"] == 1) {
                        $dataOrderpayment = Orders::select('total_payment', 'payment_amount')->where('order_id', $orderID)->where('active', 1)->get();

                        $total_margin = 0;
                        $margin = 0;
                        DB::table('orders')->where('order_id', $orderID)->update(['type' => 2]); //type 2 == ชำระหนี้
                        if ($request->input("member_id")) {
                            $dataPointCurrent = Member::select("point")->where('member_id',  $request->input("member_id"))->where('active', 1)->first();
                            if ($dataPointInt == 1) { //payment_amount = point
                                $payForPoint = $dataPointCurrent->point;
                                Member::where('member_id', $request->input("member_id"))->update(['point' => $payForPoint]);
                            } elseif ($dataPointInt == 2) { //qty = point
                                $qtyForPoint = $dataPointCurrent->point;
                                Member::where('member_id', $request->input("member_id"))->update(['point' => $qtyForPoint]);
                            }
                        }
                    } else {
                        if ($request->input("member_id")) {
                            $dataPointCurrent = Member::select("point")->where('member_id',  $request->input("member_id"))->where('active', 1)->first();
                            if ($dataPointInt == 1) { //payment_amount = point
                                $payForPoint = $dataPointCurrent->point + $dataOrderpayment[0]->payment_amount;
                                Member::where('member_id', $request->input("member_id"))->update(['point' => $payForPoint]);
                            } elseif ($dataPointInt == 2) { //qty = point
                                $qtyForPoint = $dataPointCurrent->point  + $sumQty;
                                Member::where('member_id', $request->input("member_id"))->update(['point' => $qtyForPoint]);
                            }
                        }
                    }
                } else {
                    DB::rollBack();
                    return response()->json([
                        "success" => FALSE,
                        "message" => " don't have order_payment",
                    ]);
                }
                DB::commit();
                return response()->json([
                    "success" => TRUE,
                    "message" => "success payment",
                    "order_id" => $orderID,
                ]);
            } else {
                DB::rollBack();

                return response()->json([
                    "success" => FALSE,
                    "message" => "user don't have session.",
                ]);
            }
        } else {
            return response()->json([
                $validPayment,

            ], 400);
        }
    }

    public function showbyid($id)
    {
        $dataQueue = MetaData::select('meta_value')->where("active", 1)->where("meta_module", 'store')->where("meta_key", 'store_order')->first();
        $dataReceiptNumber = MetaData::select('meta_value')->where("active", 1)->where("meta_module", 'printer')->where("meta_key", 'receipt_number')->first();
        $datecurrent = Carbon::now()->timezone('Asia/Bangkok')->format("Y-m-d");
        $count = DB::table('orders')->select('*')->where('created_datetime', 'LIKE', "%$datecurrent%")->count();
        $count = $count + 1;
        $order = OrderReources::collection(Orders::where("active", 1)->where("order_id", $id)->get());
        $prefix_number = "";
        if ($dataReceiptNumber["meta_value"] == 1) {
            foreach ($order as $orders) {
                $prefix_number = $orders->session_receipt_number;
            }
        } elseif ($dataReceiptNumber["meta_value"] == 2) {
            foreach ($order as $orders) {
                $prefix_number = $orders->year_receipt_number;
            }
        }

        if (!$order->isEmpty()) {
            if ($dataQueue["meta_value"] == 1) {
                return response()->json([
                    "success" => true,
                    "message" => "Bill found.",
                    "order_queue" => $count,
                    "prefix_number" => $prefix_number,
                    "data" => $order
                ]);
            } else {
                return response()->json([
                    "success" => true,
                    "message" => "Bill found.",
                    "prefix_number" => $prefix_number,
                    "data" => $order
                ]);
            }
        } else {
            return response()->json([
                "success" => FALSE,
                "message" => "Bill not found.",
            ]);
        }
    }

    public function showbyDate(Request $request)
    {
        $user = auth()->user();

        $StartDateTime = $request->input('StartDateTime');
        $EndDateTime = $request->input('EndDateTime');
        $EndDateTime .= " 23:59";
        // var_dump($EndDateTime);
        // $test = Orders::where('user_id', 1,)->where("active", 1)->where('created_datetime',  '>=', $StartDateTime)
        //     ->where('created_datetime', '<=', $EndDateTime)->get();
        // var_dump($test);
        if ($StartDateTime && $EndDateTime) {
            $order = OrderReources::collection(Orders::where('user_id', $user->id,)->where("active", 1)->where('created_datetime',  '>=', $StartDateTime)
                ->where('created_datetime', '<=', $EndDateTime)->orderByDesc('created_datetime')->get());
            return response()->json([
                "success" => true,
                "message" => "Bill found.",
                "data" => $order
            ]);
        } elseif (!$StartDateTime || !$EndDateTime) {
            $order = OrderReources::collection(Orders::where('user_id', $user->id,)->where("active", 1)->orderByDesc('created_datetime')->get());
            $data = $order->sortByDesc('created_datetime');
            return response()->json([
                "success" => true,
                "message" => "Bill order all found.",
                "data" => $order
            ]);
        } else {

            return response()->json([
                "success" => false,
                "message" => "Bill not found.",
            ]);
        }
    }


    private function validateRefund(Request $request)
    {
        $dataOrderPrice = -1;
        if (!$request->input("order_lines") == []) {
            $jsonarray = json_decode(json_encode($request->input("order_lines")), TRUE);

            foreach ($jsonarray as $data_order_line) {
                $dataOrder = Order_lines::select('price')->where('order_id', $data_order_line["order_id"])->where('product_id', $data_order_line["product_id"])->where('active', 1)->get();

                if ($data_order_line["product_uom_id"] > 0) {
                    $dataPriceUom = Product_uom::select('price')->where('product_uom_id', $data_order_line["product_uom_id"])->where('product_id', $data_order_line["product_id"])->where('active', 1)->get();
                    $old_price = $dataPriceUom[0]->price * $dataOrderPrice;
                    if ($old_price == $data_order_line["price"]) {
                        return [
                            "success" => true,
                        ];
                    } else {
                        return
                            [
                                "success" => FALSE,
                                "message" => "price is not the same price UOM",
                            ];
                    }
                } elseif ($dataOrder->count() >= 1) {

                    $old_price = $dataOrder[0]->price * $dataOrderPrice;

                    if ($old_price == $data_order_line["price"]) {
                        return [
                            "success" => true,
                        ];
                    } else {
                        return [
                            "success" => false,
                            "message" => "price is not the same old price",
                        ];
                    }
                } else {
                    return [
                        "success" => false,
                        "message" => "Product In Old Bill Not Found",
                    ];
                }
            }
        } else {
            return [
                "success" => false,
                "message" => "Don't Have Orderlines",
            ];
        }
    }
    public function refund(Request $request, $order_id)
    {

        // 'old_order_id' => 'required',
        $validPayment = $this->validateRefund($request); //validate
        $order_number_code = $this->orderNumberCode(); //generatecode
        $year_receipt_number = $this->year_receipt_number();
        $session_receipt_number = $this->session_receipt_number();
        $datetimecurrent = $this->datetimecurrent(); //datatimecurrent
        $Datavat = MetaData::select('meta_value')->where("active", 1)->where("meta_module", 'store')->where("meta_key", 'store_vat')->first();
        $vat = (int)$Datavat["meta_value"];
        $jsonarray = json_decode(json_encode($request->input()), TRUE);
        $user = auth()->user();
        $validator = Validator::make($request->all(), [
            'is_vat' => 'required',
            'total_payment' => 'required',
            'order_lines' => 'required',
        ]);
        if ($validator->fails()) {
            return response()->json([
                "status" => false,
                "messageError" => $validator->errors()->toJson()
            ], 400);
        }
        DB::beginTransaction();
        $dataset = [];
        $dataPoint = MetaData::select('meta_value')->where("active", 1)->where("meta_module", 'store')->where("meta_key", 'store_point')->first();
        $dataPointInt = (int)$dataPoint["meta_value"];
        $dataVatIncluded = MetaData::select('meta_value')->where("active", 1)->where("meta_module", 'store')->where("meta_key", 'store_vat_include')->first();
        $dataVatIncludedInt = (int)$dataVatIncluded["meta_value"];

        if ($validPayment["success"] == true) {
            $possession_id = PosSession::select('pos_session_id')->where('user_id', $user->id)->where('active', 1)->get();
            if ($possession_id->count() == 1) {
                $orderID  = Orders::insertGetId([
                    'order_number' => $order_number_code,
                    'session_receipt_number' => $session_receipt_number,
                    'year_receipt_number' => $year_receipt_number,
                    'subtotal' => 0,
                    'vat' => 0,
                    'total' => 0,
                    'total_payment' => $request->input("total_payment"),
                    'total_recive' => 0,
                    'total_margin' => 0,
                    'price_change' => 0,
                    'type' => 0,
                    'payment_amount' => $request->input("total_payment"),
                    'user_id' => $user->id,
                    'member_id' => $request->input("member_id"),
                    'pos_session_id' => $possession_id[0]->pos_session_id,
                    'created_datetime' => $datetimecurrent,
                    'is_vat' => $request->input("is_vat"),
                    'pricelist_id' => $request->input("pricelist_id")
                ]);
                if (!$request->input("order_lines") == []) {
                    $jsonarray = json_decode(json_encode($request->input("order_lines")), TRUE);
                    foreach ($jsonarray as $data_order_line) {
                        $dataProduct = Product::select('current_average_cost', "is_vat")->where('product_id', $data_order_line["product_id"])->where('active', 1)->get();
                        if ($dataProduct[0]->is_vat == 1 &&  $request->input("is_vat") == 1) {
                            $margin = $data_order_line["price"] + $dataProduct[0]->current_average_cost;

                            if ($data_order_line["product_uom_id"]) {
                                $dataProductUom = Product_uom::select("multiple_qty", "price")->where('product_uom_id', $data_order_line["product_uom_id"])->where('active', 1)->first();
                                $price_uom = $dataProductUom->price / $dataProductUom->multiple_qty;
                                $margin = $price_uom + $dataProduct[0]->current_average_cost;
                                $total_margin = $margin * $dataProductUom->multiple_qty;
                            } else {
                                $total_margin = $margin * $data_order_line["qty"];
                            }

                            if ($dataVatIncludedInt == 0) {
                                $vat_calculate = $data_order_line["price"] * ($vat / 100);
                                $total_vat = $vat_calculate * $data_order_line["qty"];
                                $subtotal = ($data_order_line["price"] * $data_order_line["qty"]);
                                $total = $total_vat + $subtotal;
                            } else if ($dataVatIncludedInt == 1) {
                                $vat_calculate = $data_order_line["price"] * ($vat / (100 + $vat)); /*การคิดVATใน*/
                                $total_vat = $vat_calculate * $data_order_line["qty"];
                                $subtotal = ($data_order_line["price"] * $data_order_line["qty"]) - $total_vat;/*การคิดVATใน*/
                                $total = $total_vat + $subtotal;
                            } else {
                                $vat_calculate = 0;
                                $total_vat = $vat_calculate * $data_order_line["qty"];
                                $subtotal = ($data_order_line["price"] * $data_order_line["qty"]);
                                $total = $total_vat + $subtotal;
                            }

                            $obj['order_id'] = $orderID;
                            $obj['product_id'] = $data_order_line["product_id"];
                            $obj["product_uom_id"] = $data_order_line["product_uom_id"];
                            // $obj["promotion_id"] = $data_order_line["promotion_id"];
                            $obj['qty'] = $data_order_line["qty"] * (-1);
                            $obj['price'] = $data_order_line["price"];
                            $obj['margin'] = $margin;
                            $obj["vat"] = $vat_calculate;
                            $obj['total_vat'] = $total_vat;
                            $obj['total_margin'] = $total_margin;
                            $obj['subtotal'] = $subtotal;
                            $obj['total'] = $total;
                            $obj['is_edit'] = $data_order_line["is_edit"];
                            array_push($dataset, $obj);
                        } else {
                            $subtotal = ($data_order_line["price"] * $data_order_line["qty"]);
                            $total = 0 + $subtotal;
                            $margin = $data_order_line["price"] + $dataProduct[0]->current_average_cost;
                            if ($data_order_line["product_uom_id"]) {
                                $dataProductUom = Product_uom::select("multiple_qty", "price")->where('product_uom_id', $data_order_line["product_uom_id"])->where('active', 1)->first();
                                $price_uom = $dataProductUom->price / $dataProductUom->multiple_qty;
                                $margin = $price_uom - $dataProduct[0]->current_average_cost;
                                $total_margin = $margin * ($dataProductUom->multiple_qty * (-1));
                                $margin = $total_margin;
                            } else {
                                $total_margin = $margin * $data_order_line["qty"];
                            }
                            $obj['order_id'] = $orderID;
                            $obj['product_id'] = $data_order_line["product_id"];
                            $obj["product_uom_id"] = $data_order_line["product_uom_id"];
                            // $obj["promotion_id"] = $data_order_line["promotion_id"];
                            $obj['qty'] = $data_order_line["qty"] * (-1);
                            $obj['price'] = $data_order_line["price"];
                            $obj['margin'] = $margin;
                            $obj["vat"] = 0;
                            $obj['total_vat'] = 0;
                            $obj['total_margin'] = $total_margin;
                            $obj['subtotal'] = $subtotal;
                            $obj['total'] = $total;
                            $obj['is_edit'] = $data_order_line["is_edit"];
                            array_push($dataset, $obj);
                        }
                    }
                    $order_ine_id = Order_lines::insert($dataset);
                } else {
                    DB::rollBack();
                    return response()->json([
                        "success" => FALSE,
                        "message" => " don't have order_lines",
                    ]);
                }
                $dataOrder = Order_lines::select('*')->where('order_id', $orderID)->where('active', 1)->get();
                $dataOrderpayment =  Orders::select('total_payment', 'payment_amount')->where('order_id', $orderID)->where('active', 1)->get();
                $count =  Order_lines::select('*')->where('order_id', $orderID)->where('active', 1)->count();
                $jsonarray = json_decode(json_encode($dataOrder), TRUE);
                $subtotal = 0;
                $vat = 0;
                $total = 0;
                $sumQty = 0;
                $total_margin = 0;
                $dataStockMove = [];

                foreach ($jsonarray as $data) {
                    $dataProduct = Product::select("stock_qty")->where('product_id', $data["product_id"])->where('active', 1)->get();
                    $sumSubtotal = $data["subtotal"];
                    $subtotal += $sumSubtotal;
                    $sumVat = $data["total_vat"];
                    $vat += $sumVat;
                    $sumTotal = $data["total"];
                    $total += $sumTotal;
                    $sumQty += $data["qty"];
                    $sumTotal_margin = $data["total_margin"];
                    $total_margin += $sumTotal_margin;
                    $total_stock = $dataProduct[0]->stock_qty + ($data["qty"] * (-1));
                    $total_stock_uom = 0;
                    if (!$data["product_uom_id"] == "") {
                        $dataProductUom = Product_uom::select("multiple_qty")->where('product_uom_id', $data["product_uom_id"])->where('product_id', $data["product_id"])->where('active', 1)->get();
                        $total_stock_uom =  $dataProductUom[0]->multiple_qty * ($data["qty"] * (-1));
                        $total_stock_uom =  $dataProduct[0]->stock_qty + $total_stock_uom;
                        // $total_margin += $dataProductUom[0]->multiple_qty * $sumTotal_margin;

                        Product::where('product_id', $data["product_id"])->update([
                            'stock_qty' => $total_stock_uom
                        ]);
                    } else {
                        Product::where('product_id', $data["product_id"])->update([
                            'stock_qty' => $total_stock
                        ]);
                    }
                    $dataQty = $data["qty"];
                    $obj = [
                        'product_id' => $data["product_id"],
                        'ref_type' => 'App\Models\Orders',
                        'ref_id' => $data["order_id"],
                        'qty' => $dataQty,
                        'create_datetime' => $datetimecurrent
                    ];
                    array_push($dataStockMove, $obj);
                }
                Stock_move::insert($dataStockMove);

                // $datOrderPay = [
                //     'order_id' => $orderID,
                //     'payment_id' => 1,
                //     'amount' => $total
                // ];
                // Order_payment::insert($datOrderPay);


                if (!$request->input("order_payment") == []) {
                    $total_amount = 0;
                    $jsonarray = json_decode(json_encode($request->input("order_payment")), TRUE);
                    $dataDebt = Member::where('member_id',  $request->input("member_id"))->where('active', 1)->get();
                    foreach ($jsonarray as $data_order_payment) {
                        $total_amount += $data_order_payment["amount"];
                        if ($data_order_payment["payment_id"] == 1) {
                            $data = [
                                'order_id' => $orderID,
                                'payment_id' => $data_order_payment["payment_id"],
                                'amount' => $data_order_payment["amount"] * (-1)
                            ];
                        } else {
                            $data = [
                                'order_id' => $orderID,
                                'payment_id' => $data_order_payment["payment_id"],
                                'amount' => $data_order_payment["amount"] * (-1)
                            ];
                        }
                        if ($data_order_payment["payment_id"] == 2) {
                            $debtCurrent = $dataDebt[0]->debt + ($data_order_payment["amount"] * (-1));
                            Order_payment::insert($data);
                            DB::table('member')->where('member_id', $request->input("member_id"))->update(['debt' => $debtCurrent]);
                        } elseif ($data_order_payment["payment_id"] == 3) {
                            Order_payment::insert($data);
                        } else {
                            Order_payment::insert($data);
                        }
                        if ($request->input("member_id")) {
                            $dataPointCurrent = Member::select("point")->where('member_id',  $request->input("member_id"))->where('active', 1)->first();
                            if ($dataPointInt == 1) { //payment_amount = point
                                $payForPoint = $dataPointCurrent->point - $data_order_payment["amount"];
                                Member::where('member_id', $request->input("member_id"))->update(['point' => $payForPoint]);
                            } elseif ($dataPointInt == 2) { //qty = point
                                $qtyForPoint = $dataPointCurrent->point - $sumQty;
                                Member::where('member_id', $request->input("member_id"))->update(['point' => $qtyForPoint]);
                            }
                        }
                    }
                    $total_amount = $total_amount * (-1);
                    if ($total_amount != $request->input("total_payment")) {
                        DB::rollBack();
                        return response()->json([
                            "success" => FALSE,
                            "message" => " ยอดคืนสินค้ากับยอดเงินคืนไม่เท่ากัน",
                        ]);
                    }
                } else {
                    DB::rollBack();
                    return response()->json([
                        "success" => FALSE,
                        "message" => "ไม่มีข้อมูลการจ่ายเงิน",
                    ]);
                }
                Orders::where('order_id', $orderID)->update([
                    'subtotal' => $subtotal,
                    'vat' => $vat,
                    // 'total_payment' => $total,
                    'total' => $total,
                    'total_margin' => $total_margin,
                    // 'payment_amount' => $total,
                ]);
                Orders::where('order_id', $order_id)->update([
                    'type' => 0
                ]);
                DB::commit();
                return response()->json([
                    "success" => TRUE,
                    "message" => "success refund",
                    "order_id" => $orderID,
                ]);
            } else {
                DB::rollBack();

                return response()->json([
                    "success" => FALSE,
                    "message" => "user don't have session.",
                ]);
            }
        } else {
            return response()->json([
                $validPayment,
            ], 400);
        }
    }


    //     public function showbill(Request $request)
    //     {
    //         $dataProductmodel='App\Models\Product';
    //         $dataProduct='Orders';
    //         $id='2';
    //         // $data = DB::table($dataProduct)->select('*')->where('product_id', $id)->get();
    //         $data = $dataProductmodel::find($id);

    //         // $data =  $dataProduct::all();
    // //   var_dump($data);
    //       return response()->json([
    //         "success" => true,
    //         "message" => "bill List",
    //         "data" => $data
    //       ]);
    //     }
}
