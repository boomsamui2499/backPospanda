<?php

namespace App\Http\Controllers;

use App\Chart;

use App\Http\Resources\PurchaseOrderResource;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Arr;
use Illuminate\Http\Request;
use App\Models\Expiration_log;
use App\Http\Resources\OrderReources;
use App\Models\Order_lines;
use App\Models\PosSession;
use App\Models\Orders;
use App\Models\Out_of_stock;
use App\Models\Product;
use App\Models\Product_uom;
use Illuminate\Support\Facades\Validator;
use Date;
use DateTime;
use Order;
use PhpParser\Node\Stmt\Else_;
use PHPUnit\TextUI\XmlConfiguration\Group;

class DashboardController extends Controller
{
    private function datecurrent()
    {
        $dt = Carbon::now()->zone('Asia/Bangkok');
        $toDay = $dt->format('d');
        $toMonth = $dt->format('m');
        $toYear = $dt->format('Y');
        $dateUTC = Carbon::createFromDate($toYear, $toMonth, $toDay, 'UTC');
        $datePST = Carbon::createFromDate($toYear, $toMonth, $toDay, 'Asia/Bangkok');
        $difference = $dateUTC->diffInHours($datePST);
        $datecurrent = $dt->addHours($difference);
        return $datecurrent;
    }

    public function DashboardPurchase(Request $request)
    {

        $startDate = $request->input('startDate');
        $endDate = $request->input('endDate');
        $data =
            DB::table('purchase_order')
            ->join('purchase_order_line', 'purchase_order.purchase_order_id', '=', 'purchase_order_line.purchase_order_id')
            ->join('product', 'purchase_order_line.product_id', '=', 'product.product_id')
            ->select('purchase_order_line.product_id', 'product_name', DB::raw("SUM(qty) as qtytotal"), DB::raw("SUM(purchase_order_line.price * qty) as pricetotal"))
            ->where("purchase_order.active", 1)->where('purchase_order.create_datetime',  '>=', $startDate)
            ->where('purchase_order.create_datetime', '<=', $endDate)
            ->where('purchase_order_line.product_id', '!=', 1)
            ->groupBy("purchase_order_line.product_id")
            ->get();

        $dataset = [];
        $total = 0;

        if (!$data->isEmpty()) {
            foreach ($data as $datas) {
                $dataset['labels'][] = $datas->product_name;
                $dataset['datasets']["dataqty"]["data"][] = intval($datas->qtytotal);
                $dataset['datasets']["dataprice"]["data"][] = $datas->pricetotal;
                $total += $datas->pricetotal;
            }
            $dataset['datasets']["dataqty"]["label"] = "จำนวน";
            $dataset['datasets']["dataprice"]["label"] = "ยอดรวมการรับสินค้า";

            return response()->json([
                "success" => true,
                "message" => "Data found.",
                "data" => $dataset,
                "total" => $total
            ]);
        } else {
            return response()->json([
                "success" => FALSE,
                "message" => "Data Not found.",
            ]);
        }
    }
    public function DashboardPurchaseTable(Request $request)
    {
        $total = 0;

        $startDate = $request->input('startDate');
        $endDate = $request->input('endDate');
        $data =
            DB::table('purchase_order')
            ->join('purchase_order_line', 'purchase_order.purchase_order_id', '=', 'purchase_order_line.purchase_order_id')
            ->join('product', 'purchase_order_line.product_id', '=', 'product.product_id')
            ->select('purchase_order_line.product_id', 'product_name', DB::raw("SUM(purchase_order_line.qty) as qtytotal"), DB::raw("SUM(purchase_order_line.price * qty) as pricetotal"))
            ->where("purchase_order.active", 1)->where('purchase_order.create_datetime',  '>=', $startDate)
            ->where('purchase_order.create_datetime', '<=', $endDate)
            ->where('purchase_order_line.product_id', '!=', 1)
            ->groupBy("purchase_order_line.product_id")
            ->get();

        foreach ($data as  $datas) {
            $total += $datas->pricetotal;
        }
        if (!$data->isEmpty()) {
            return response()->json([
                "success" => true,
                "message" => "Data found.",
                "data" => $data,
                "total" => $total
            ]);
        } else {
            return response()->json([
                "success" => FALSE,
                "message" => "Data Not found.",
            ]);
        }
    }
    public function DashboardPurchasemonth(Request $request)
    {

        $startDate = $request->input('startDate');
        $endDate = $request->input('endDate');
        $dataset = [
            'labels' => array(),
            'datasets' => [
                'dataBymonth' => [
                    'data' => array(),
                    'label' => array()
                ]
            ]
        ];
        $total = 0;
        $startDate = strtotime($startDate);
        $firstStartDate = date('Y-m-01', $startDate);
        $endDate = strtotime($endDate);
        $endDateofMonth = date('Y-m-d', $endDate);
        $period = CarbonPeriod::create($firstStartDate, '1 month', $endDateofMonth);
        foreach ($period as $date) {
            $result = DB::table('purchase_order')
                ->select('purchase_order.create_datetime', DB::raw("SUM(total) as sumtotal"), DB::raw("DATE_FORMAT(purchase_order.create_datetime, '%m-%Y') new_date"))
                ->where("purchase_order.active", 1)->where('purchase_order.create_datetime',  '>=', $date->firstOfMonth()->format('Y-m-d'))
                ->where('purchase_order.create_datetime', '<=', $date->endOfMonth()->format('Y-m-d'))
                ->orderBy('purchase_order.create_datetime')
                ->groupby('new_date')
                ->get();
            array_push($dataset['labels'], $date->format('Y-m'));
            if (!$result->isEmpty()) {
                array_push($dataset['datasets']["dataBymonth"]["data"], $result[0]->sumtotal);
                $total += $result[0]->sumtotal;
            } else {
                array_push($dataset['datasets']["dataBymonth"]["data"], 0);
            }
        }
        array_push($dataset['datasets']["dataBymonth"]['label'], "ยอดรับสินค้า");

        return response()->json([
            "success" => true,
            "message" => "Data found.",
            "data" => $dataset,
            "total" => $total
        ]);
    }
    public function DashboardPurchasemonthTable(Request $request)
    {
        $startDate = $request->input('startDate');
        $endDate = $request->input('endDate');
        $total = 0;
        $dataset = [];
        $startDate = strtotime($startDate);
        $firstStartDate = date('Y-m-01', $startDate);
        $endDate = strtotime($endDate);
        $endDateofMonth = date('Y-m-d', $endDate);
        $period = CarbonPeriod::create($firstStartDate, '1 month', $endDateofMonth);
        foreach ($period as $date) {
            $result = DB::table('purchase_order')
                ->select('create_datetime', DB::raw("SUM(total) as sumtotal"), DB::raw("DATE_FORMAT(create_datetime, '%m-%Y') new_date"))
                ->where("purchase_order.active", 1)->where('purchase_order.create_datetime',  '>=', $date->firstOfMonth()->format('Y-m-d'))
                ->where('purchase_order.create_datetime', '<=', $date->endOfMonth()->format('Y-m-d'))
                ->orderBy('create_datetime')
                ->groupby('new_date')
                ->get();
            if (!$result->isEmpty()) {
                $data["new_date"] =    $result[0]->new_date;
                $data["sumtotal"] =   $result[0]->sumtotal;
                array_push($dataset, $data);
                $total += $result[0]->sumtotal;
            } else {
                $data["new_date"] =    $date->format('m-Y');
                $data["sumtotal"] =    0;
                array_push($dataset, $data);
            }
        }

        // if (!$result->isEmpty()) {
        return response()->json([
            "success" => true,
            "message" => "Data found.",
            "data" => $dataset,
            "total" => $total

        ]);
        // } else {
        //     return response()->json([
        //         "success" => FALSE,
        //         "message" => "Data Not found.",
        //     ]);
        // }
    }

    public function DashboardExpirationTable(Request $request)
    {
        $number = $request->input('number');
        $startDateCur = Carbon::now()->timezone('Asia/Bangkok')->format('Y-m-d');
        $endDateCur = Carbon::now()->timezone('Asia/Bangkok')->addDays($number)->format('Y-m-d');
        $data = Expiration_log::with('product')->where('active', 1)->where('expired_datetime',  '>=', $startDateCur)->where('expired_datetime', '<=', $endDateCur)->orderBy('created_datetime')->get();
        $newData = array();
        if (!$data->isEmpty()) {
            foreach ($data as $data) {
                $obj['expiration_log_id'] = $data->expiration_log_id;
                $obj['lot_number'] = $data->lot_number;
                $obj['expired_datetime'] = $data->expired_datetime;
                $obj['product_name'] = $data["product"]->product_name;
                array_push($newData, $obj);
            }
            return response()->json([
                "success" => true,
                "message" => "Data found.",
                "data" => $newData,
            ]);
        } else {
            return response()->json([
                "success" => FALSE,
                "message" => "Data Not found.",
            ]);
        }
    }
    public function DashboardOrder(Request $request)
    {

        $startDate = $request->input('startDate');
        $endDate = $request->input('endDate');
        $dataset  = [
            'labels' => array(),
            'datasets' => [
                'dataTotal' => [
                    'data' => array(),
                    'label' => array()

                ],
                'dataCost' => [
                    'data' => array(),
                    'label' => array()

                ],
                'dataVat' => [
                    'data' => array(),
                    'label' => array()

                ]
            ]
        ];
        $startDate = strtotime($startDate);
        $firstStartDate = date('Y-m-01', $startDate);
        $endDate = strtotime($endDate);
        $endDateofMonth = date('Y-m-d', $endDate);
        $period = CarbonPeriod::create($firstStartDate, '1 month', $endDateofMonth);
        $n = 0;
        $total = 0;
        $totalcost = 0;
        $totalvat = 0;
        foreach ($period as $date) {
            // $resultpurchase = DB::table('purchase_order')
            //     ->select('purchase_order.create_datetime', DB::raw("SUM(total) as cost"), DB::raw("DATE_FORMAT(purchase_order.create_datetime, '%m-%Y') new_date"))
            //     ->where("purchase_order.active", 1)->where('purchase_order.create_datetime',  '>=', $date->firstOfMonth()->format('Y-m-d'))
            //     ->where('purchase_order.create_datetime', '<=', $date->endOfMonth()->format('Y-m-d'))
            //     ->orderBy('purchase_order.create_datetime')
            //     ->groupby('new_date')
            //     ->get();
            $result = DB::table('orders')
                ->select('created_datetime', DB::raw("SUM(total) as sumtotal"), DB::raw("SUM(subtotal) as sumsubtotal"), DB::raw("SUM(total_margin) as totalMargin"), DB::raw("SUM(vat) as totalVat"), DB::raw("DATE_FORMAT(created_datetime, '%m-%Y') new_date"))
                ->where("orders.active", 1)->where('orders.created_datetime',  '>=', $date->firstOfMonth()->format('Y-m-d H:i:s'))
                ->where('orders.created_datetime', '<=', $date->endOfMonth()->format('Y-m-d H:i:s'))
                ->orderBy('created_datetime')
                ->groupby('new_date')
                ->get();
            array_push($dataset['labels'], $date->format('Y-m'));
            // if (!$resultpurchase->isEmpty()) {
            //     // if ($resultpurchase->count() == 1) {
            //     $totalcost += floor($resultpurchase[0]->cost * 100) / 100;
            //     array_push($dataset['datasets']["dataCost"]["data"],  $resultpurchase[0]->cost);
            // } else {
            //     array_push($dataset['datasets']["dataCost"]["data"],  0);
            // }
            // $n += 1;
            // var_dump($result[0]->sumtotal);
            // var_dump($n + 1);
            if (!$result->isEmpty()) {
                $totalvatall = floor($result[0]->totalVat * 100) / 100;
                $total += floor($result[0]->sumtotal * 100) / 100;
                $totalvat += floor($result[0]->totalVat * 100) / 100;
                $totalsub = floor($result[0]->sumsubtotal * 100) / 100;
                $totalmargin = floor($result[0]->totalMargin * 100) / 100;
                $totalcost = floor(($totalsub - $totalmargin) * 100) / 100;

                array_push($dataset['datasets']["dataTotal"]["data"], floatval($result[0]->sumtotal * 100) / 100);
                array_push($dataset['datasets']["dataVat"]["data"],  floatval($result[0]->totalVat * 100) / 100);
                array_push($dataset['datasets']["dataCost"]["data"],  floatval($totalcost * 100) / 100);
            } else {
                array_push($dataset['datasets']["dataTotal"]["data"],  0);
                array_push($dataset['datasets']["dataCost"]["data"],  0);
                array_push($dataset['datasets']["dataVat"]["data"],  0);
            }
        }
        array_push($dataset['datasets']["dataTotal"]["label"], "ยอดรวมรายได้");
        array_push($dataset['datasets']["dataCost"]["label"], "ยอดรวมต้นทุน");
        array_push($dataset['datasets']["dataVat"]["label"], "ยอดรวมภาษี");
        $dataset['total'] = floor($total * 100) / 100;
        $dataset['totalcost'] = floor($totalcost * 100) / 100;
        $dataset['totalVat'] =  floor($totalvat * 100) / 100;
        return response()->json([
            "success" => true,
            "message" => "Data found.",
            "data" => $dataset,
        ]);
    }



    public function DashboardOrderDay(Request $request)
    {

        $startDate = $request->input('startDate');
        $endDate = $request->input('endDate');
        $dataset  = [
            'labels' => array(),
            'datasets' => [
                'dataTotal' => [
                    'data' => array(),
                    'label' => array()

                ],
                'dataCost' => [
                    'data' => array(),
                    'label' => array()

                ],
                'dataMargin' => [
                    'data' => array(),
                    'label' => array()

                ]
            ]
        ];
        $period = CarbonPeriod::create($startDate, '1 day', $endDate);
        $total = null;
        $totalcost = null;
        foreach ($period as $date) {
            $result = DB::table('orders')
                ->select('created_datetime',  DB::raw("SUM(subtotal) as sumsubtotal"), DB::raw('count(order_number) as bill_count'), DB::raw("SUM(total) as sumtotal"), DB::raw("SUM(total_margin) as sumtotalmargin"), DB::raw("DATE(created_datetime) newday"))
                ->where("orders.active", 1)->where("orders.type", '!=', 2)->where('orders.created_datetime',  '>=', $date->format('Y-m-d H:i:s'))
                ->where('orders.created_datetime', '<=', $date->endOfday()->format('Y-m-d H:i:s'))
                ->orderBy('created_datetime')
                ->groupby('newday')
                ->get();
            array_push($dataset['labels'], $date->format('Y-m-d'));

            if (!$result->isEmpty()) {
                $total += $result[0]->sumtotal;
                array_push($dataset['datasets']["dataTotal"]["data"], floor($result[0]->sumtotal * 100) / 100);
                array_push($dataset['datasets']["dataCost"]["data"],  floor(($result[0]->sumsubtotal - $result[0]->sumtotalmargin) * 100) / 100);
                array_push($dataset['datasets']["dataMargin"]["data"], floor($result[0]->sumtotalmargin * 100) / 100);
            } else {
                array_push($dataset['datasets']["dataTotal"]["data"], 0);
                array_push($dataset['datasets']["dataMargin"]["data"], 0);
                array_push($dataset['datasets']["dataCost"]["data"],  0);
            }
        }
        array_push($dataset['datasets']["dataTotal"]["label"], "ยอดขายรายวัน");
        array_push($dataset['datasets']["dataCost"]["label"], "ยอดต้นทุนรายวัน");
        array_push($dataset['datasets']["dataMargin"]["label"], "ยอดกำไรรายวัน");
        $dataset['total'] = $total;

        return response()->json([
            "success" => true,
            "message" => "Data found.",
            "data" => $dataset
        ]);
    }
    public function DashboardOrderDayTable(Request $request)
    {
        $startDate = $request->input('startDate');
        $endDate = $request->input('endDate');
        $dataset = [];
        $total = null;
        $period = CarbonPeriod::create($startDate, '1 day', $endDate);
        foreach ($period as $date) {
            $result = DB::table('orders')
                ->select('created_datetime', DB::raw("SUM(subtotal) as sumsubtotal"), DB::raw('count(order_number) as bill_count'), DB::raw("SUM(total) as sumtotal"),  DB::raw("SUM(total_margin) as sumtotalmargin"), DB::raw("DATE(created_datetime) newday"))
                ->where("orders.active", 1)->where("orders.type", '!=', 2)->where('orders.created_datetime',  '>=', $date->format('Y-m-d H:i:s'))
                ->where('orders.created_datetime', '<=', $date->endOfday()->format('Y-m-d H:i:s'))
                ->orderBy('created_datetime')
                ->groupby('newday')
                ->get();
            // $resultpurchase = DB::table('purchase_order')
            //     ->select('purchase_order.create_datetime', DB::raw("SUM(total) as cost"), DB::raw("DATE_FORMAT(purchase_order.create_datetime, '%m-%Y') new_date"))
            //     ->where("purchase_order.active", 1)->where('purchase_order.create_datetime',  '>=', $date->format('Y-m-d'))
            //     ->where('purchase_order.create_datetime', '<=', $date->endOfday()->format('Y-m-d'))
            //     ->orderBy('purchase_order.create_datetime')
            //     ->groupby('new_date')
            //     ->get();
            // if (!$resultpurchase->isEmpty()) {
            //     $data["cost"] =   $resultpurchase[0]->cost;
            // } else {
            //     $data["cost"] =    0;
            // }
            if (!$result->isEmpty()) {
                $total += $result[0]->sumtotal;
                $data["cost"] =   floor(($result[0]->sumsubtotal - $result[0]->sumtotalmargin) * 100) / 100;

                $data["new_date"] =   $date->format('Y-m-d');
                $data["count"] =   $result[0]->bill_count;
                $data["sumtotal"] =   floor($result[0]->sumtotal * 100) / 100;
                $data["sumtotalmargin"] =   floor($result[0]->sumtotalmargin * 100) / 100;
                array_push($dataset, $data);
            } else {
                $data["new_date"] =   $date->format('Y-m-d');
                $data["count"] =   0;
                $data["sumtotal"] =    0;
                $data["cost"] =  0;
                $data["sumtotalmargin"] =    0;
                $total += 0;
                array_push($dataset, $data);
            }
        }

        if (!$dataset == []) {
            return response()->json([
                "success" => true,
                "message" => "Data found.",
                "data" => $dataset,
                "Totalresult" => floor($total * 100) / 100
            ]);
        } else {
            return response()->json([
                "success" => FALSE,
                "message" => "Data Not found.",
            ]);
        }
    }
    public function DashboardOrderMonth(Request $request)
    {

        $startDate = $request->input('startDate');
        $endDate = $request->input('endDate');
        $dataset  = [
            'labels' => array(),
            'datasets' => [
                'dataTotal' => [
                    'data' => array(),
                    'label' => array()

                ],
                'dataCost' => [
                    'data' => array(),
                    'label' => array()

                ],
                'dataMargin' => [
                    'data' => array(),
                    'label' => array()

                ]
            ]
        ];
        $startDate = strtotime($startDate);
        $firstStartDate = date('Y-m-01', $startDate);
        $endDate = strtotime($endDate);
        $endDateofMonth = date('Y-m-d', $endDate);
        $period = CarbonPeriod::create($firstStartDate, '1 month', $endDateofMonth);
        $total = 0;
        $subtotal = 0;
        $totalcost = 0;
        $totalMargin = 0;
        foreach ($period as $date) {
            $result = DB::table('orders')
                ->select('created_datetime', DB::raw("SUM(subtotal) as sumsubtotal"), DB::raw("SUM(total) as sumtotal"), DB::raw("SUM(total_margin) as totalMargin"), DB::raw("SUM(vat) as totalVat"), DB::raw("DATE_FORMAT(created_datetime, '%m-%Y') new_date"))
                ->where("orders.active", 1)->where("orders.type", '!=', 2)->where('orders.created_datetime',  '>=', $date->firstOfMonth()->format('Y-m-d H:i:s'))
                ->where('orders.created_datetime', '<=', $date->endOfMonth()->format('Y-m-d H:i:s'))
                ->orderBy('created_datetime')
                ->groupby('new_date')
                ->get();
            array_push($dataset['labels'], $date->format('Y-m'));

            if (!$result->isEmpty()) {
                $totalvatall = floor($result[0]->totalMargin * 100) / 100;
                $subtotal += $result[0]->sumsubtotal;
                $totalMargin += $result[0]->totalMargin;
                $cost =  $result[0]->sumtotal - $result[0]->totalMargin;

                $totalData = floor($result[0]->sumtotal * 100) / 100;
                $totalCost = floor($cost * 100) / 100;
                $totalMarginData = floor($result[0]->totalMargin * 100) / 100;
                $total += $totalData;

                array_push($dataset['datasets']["dataTotal"]["data"], $totalData);
                array_push($dataset['datasets']["dataCost"]["data"],  $totalCost);
                array_push($dataset['datasets']["dataMargin"]["data"],  $totalMarginData);
            } else {
                array_push($dataset['datasets']["dataTotal"]["data"],  0);
                array_push($dataset['datasets']["dataCost"]["data"],  0);
                array_push($dataset['datasets']["dataMargin"]["data"],  0);
            }
        }
        array_push($dataset['datasets']["dataTotal"]["label"], "ยอดรวมรายได้");
        array_push($dataset['datasets']["dataCost"]["label"], "ยอดรวมต้นทุน");
        array_push($dataset['datasets']["dataMargin"]["label"], "ยอดรวมกำไร");
        $dataset['total'] = floor($total * 100) / 100;
        return response()->json([
            "success" => true,
            "message" => "Data found.",
            "data" => $dataset,
        ]);
    }
    public function DashboardOrderMonthTable(Request $request)
    {
        $startDate = $request->input('startDate');
        $endDate = $request->input('endDate');
        $startDate = strtotime($startDate);
        $firstStartDate = date('Y-m-01', $startDate);
        $endDate = strtotime($endDate);
        $endDateofMonth = date('Y-m-d', $endDate);
        $period = CarbonPeriod::create($firstStartDate, '1 month', $endDateofMonth);
        $total = 0;
        $dataset = [];
        foreach ($period as $date) {
            $result = DB::table('orders')
                ->select('created_datetime', DB::raw("SUM(subtotal) as sumsubtotal"), DB::raw("SUM(total_margin) as sumtotal_margin"), DB::raw('count(order_number) as bill_count'), DB::raw("SUM(total) as sumtotal"), DB::raw("DATE_FORMAT(created_datetime, '%m-%Y') newmonth"))
                ->where("orders.active", 1)->where("orders.type", '!=', 2)->where('orders.created_datetime',  '>=', $date->firstOfMonth()->format('Y-m-d H:i:s'))
                ->where('orders.created_datetime', '<=', $date->endOfMonth()->format('Y-m-d H:i:s'))
                ->orderBy('created_datetime')
                ->groupby('newmonth')
                ->get();
            // $resultpurchase = DB::table('purchase_order')
            //     ->select('purchase_order.create_datetime', DB::raw("SUM(total) as cost"), DB::raw("DATE_FORMAT(purchase_order.create_datetime, '%m-%Y') new_date"))
            //     ->where("purchase_order.active", 1)->where('purchase_order.create_datetime',  '>=', $date->format('Y-m-d'))
            //     ->where('purchase_order.create_datetime', '<=', $date->endOfMonth()->format('Y-m-d'))
            //     ->orderBy('purchase_order.create_datetime')
            //     ->groupby('new_date')
            //     ->get();
            // if (!$resultpurchase->isEmpty()) {
            //     $data["cost"] =   $resultpurchase[0]->cost;
            // } else {
            //     $data["cost"] =    0;
            // }
            if (!$result->isEmpty()) {
                $total += $result[0]->sumtotal;
                $data["new_date"] =   $date->format('Y-m');
                $data["count"] =   $result[0]->bill_count;
                $data["sumtotal"] =   $result[0]->sumtotal;
                $data["cost"] =   $result[0]->sumtotal - $result[0]->sumtotal_margin;
                $data["sumtotal_margin"] =   $result[0]->sumtotal_margin;
                array_push($dataset, $data);
            } else {
                $data["new_date"] =   $date->format('Y-m');
                $data["count"] =   0;
                $data["sumtotal"] =    0;
                $data["cost"] =    0;
                $data["sumtotal_margin"] =    0;
                array_push($dataset, $data);
            }
        }

        return response()->json([
            "success" => true,
            "message" => "Data found.",
            "data" => $dataset,
            "Totalresult" => $total
        ]);
    }
    public function DashboardOrderProduct(Request $request)
    {
        $startDate = $request->input('startDate');
        $endDate = $request->input('endDate');
        $endDate .= " 23:59";
        $startDate .= " 00:00";
        $data =
            DB::table('orders')
            ->join('order_lines', 'orders.order_id', '=', 'order_lines.order_id')
            ->join('product', 'order_lines.product_id', '=', 'product.product_id')
            ->select('order_lines.product_id', 'product_name', DB::raw("SUM(qty) as qtytotal"), DB::raw("SUM(abs(order_lines.price) * qty) as pricetotal"))
            ->where("orders.active", 1)->where('orders.created_datetime',  '>=', $startDate)
            ->where('orders.created_datetime', '<=', $endDate)
            ->where('order_lines.product_id', '!=', 1)
            ->groupBy("order_lines.product_id")
            ->get();

        $dataset = [];
        if (!$data->isEmpty()) {
            foreach ($data as $datas) {
                $dataset['labels'][] = $datas->product_name;
                $dataset['datasets']["dataprice"]["data"][] = floor($datas->pricetotal * 100) / 100;
            }
            $dataset['datasets']["dataprice"]["label"] = "ยอดขายแยกตามสินค้า";

            return response()->json([
                "success" => true,
                "message" => "Data found.",
                "data" => $dataset,
            ]);
        } else {
            return response()->json([
                "success" => FALSE,
                "message" => "Data Not found.",
            ]);
        }
    }
    public function DashboardOrderProductTable(Request $request)
    {
        $datasetDetailOrder = [];
        $datasetDetailPayment = [];
        $startDate = $request->input('startDate');
        $endDate = $request->input('endDate');
        // $startDate = '2024-01-22';
        // $endDate = '2024-01-22';
        $orderBy = $request->input('orderBy');
        $endDate .= " 23:59";
        $startDate .= " 00:00";
        $total = null;
        $totalprice = null;
        $resultOrderlines = array();

        if ($orderBy == 2) {
            $order_by = "pricetotal";
        } elseif ($orderBy == 1) {
            $order_by = "qtytotal";
        }
        //  else {
        //     $order_by = "order_lines.product_id";
        // }
        // $data =
        //     DB::table('orders')
        //     ->join('order_lines', 'orders.order_id', '=', 'order_lines.order_id')
        //     ->join('product', 'order_lines.product_id', '=', 'product.product_id')
        //     ->select('order_lines.product_id', 'order_lines.qty', 'order_lines.product_uom_id', 'product_name', DB::raw("SUM(qty) as qtytotal"), DB::raw("SUM(abs(order_lines.price) * qty) as pricetotal"))
        //     ->where("orders.active", 1)->where('orders.created_datetime',  '>=', $startDate)
        //     ->where('orders.created_datetime', '<=', $endDate)
        //     ->where('order_lines.product_id', '!=', 1)
        //     ->orderByDesc($order_by)
        //     // ->groupBy("order_lines.product_id")
        //     ->get();
        // // var_dump($data);
        // $dataset = [];
        // foreach ($data as $datas) {
        //     if ($datas->product_uom_id != null) {
        //         $dataUom =
        //             DB::table('product_uom')->select('*')->where('product_uom_id', $datas->product_uom_id)->get();
        //         // var_dump($dataUom[0]->multiple_qty);
        //         $total += $datas->pricetotal;
        //         $totalprice = $datas->pricetotal;
        //         $obj['product_id'] = $datas->product_id;
        //         $obj['product_name'] = $datas->product_name;
        //         $obj['qtytotal'] = ($datas->qtytotal - $datas->qty) + ($dataUom[0]->multiple_qty * $datas->qty);
        //         $obj['pricetotal'] = floor($totalprice * 100) / 100;
        //         array_push($dataset, $obj);
        //         // var_dump($dataset);
        //     } else {

        //         $total += $datas->pricetotal;
        //         $totalprice = $datas->pricetotal;
        //         $obj['product_id'] = $datas->product_id;
        //         $obj['product_name'] = $datas->product_name;
        //         $obj['qtytotal'] = $datas->qtytotal;
        //         $obj['pricetotal'] = floor($totalprice * 100) / 100;

        //         array_push($dataset, $obj);
        //     }
        // }
        // $totalResult = floor($total * 100) / 100;
        $orderlines = Orders::with('orderline')->select('order_id')
            ->where("active", 1)->where('created_datetime',  '>=', $startDate)
            ->where('created_datetime', '<=', $endDate)
            // ->orderByDesc($order_by)
            ->get();
        // var_dump($orderlines);

        for ($i = 0; $i < $orderlines->count(); $i++) {
            $count = $orderlines[$i]['orderline']->count();
            for ($j = 0; $j < $count; $j++) {
                $dataDetailOrder = null;
                $dataDetailOrder["product_id"] =   $orderlines[$i]['orderline'][$j]["product_id"];
                $dataDetailOrder["pricetotal"] =   $orderlines[$i]['orderline'][$j]["price"];
                $dataDetailOrder["qty"] =   $orderlines[$i]['orderline'][$j]["qty"];
                $dataDetailOrder["qtytotal"] =   0;
                $dataDetailOrder["product_name"] =   $orderlines[$i]['orderline'][$j]["product"]->product_name;
                if ($orderlines[$i]['orderline'][$j]["product_uom_id"]) {
                    $dataDetailOrder["product_uom_id"] =   $orderlines[$i]['orderline'][$j]["product_uom_id"];
                    $dataDetailOrder["product_uom_name"] =   $orderlines[$i]['orderline'][$j]["uom"]->product_uom_name;
                    $dataDetailOrder["multiple_qty"] =   $orderlines[$i]['orderline'][$j]["uom"]->multiple_qty;
                    // var_dump($orderlines[$i]['orderline'][$j]["qty"]);
                    // $dataDetailOrder["qty"]
                    // = $orderlines[$i]['orderline'][$j]["qty"]
                    // + $orderlines[$i]['orderline'][$j]["uom"]->multiple_qty;
                    // var_dump($dataDetailOrder["qty"]);
                }
                // die;
                array_push($datasetDetailOrder, $dataDetailOrder);
            }
        }
        $n = 0;
        $resultOrderlines = array();

        $issetArray = array();
        // var_dump($datasetDetailOrder);
        foreach ($datasetDetailOrder as $key => $value) {
            if (isset($issetArray[$value['product_id']]) != isset($value['product_id'])) {
                if (isset($value['product_uom_id'])) {
                    $value['qtytotal'] += $value['qty'] * $value['multiple_qty'];
                    $value['qty'] = $value['qtytotal'];
                    $value['pricetotal'] = $value['pricetotal'] * $value['qty'];
                    $issetArray[$value['product_id']] = array();
                    $issetArray[$value['product_id']] = $value;
                } else {
                    $value['qtytotal'] += $value['qty'];
                    $value['qty'] = $value['qtytotal'];
                    $value['pricetotal'] = $value['pricetotal'] * $value['qty'];

                    $issetArray[$value['product_id']] = array();
                    $issetArray[$value['product_id']] = $value;
                }
            } else {


                if (isset($value['product_uom_id'])) {

                    $issetArray[$value['product_id']]['qtytotal'] += $value['qty'] * $value['multiple_qty'];
                    $issetArray[$value['product_id']]['qty'] = $issetArray[$value['product_id']]['qtytotal'];
                    $issetArray[$value['product_id']]['pricetotal'] += $value['pricetotal'] * $value['qty'];
                } else if (isset($value['product_id'])) {
                    // $value['qtytotal'] += $value['qty'];
                    // $value['qtytotal'] += $value['qty'];
                    // $value['price'] = $value['price'] * $value['qty'];
                    $issetArray[$value['product_id']]['qtytotal'] += $value['qty'];
                    $issetArray[$value['product_id']]['qty'] = $issetArray[$value['product_id']]['qtytotal'];

                    $issetArray[$value['product_id']]['pricetotal'] += $value['pricetotal'] * $value['qty'];
                } else {
                    $value['qtytotal'] += $value['qty'];

                    $issetArray[$value['product_id']] = array();
                    $issetArray[$value['product_id']] = $value;
                }
            }
            $n++;
        }
        $resultOrderlines = array();
        foreach ($issetArray as $value) {
            // $resultOrderlines["total"] = $resultTotal;

            array_push($resultOrderlines, $value);
        }
        $array = collect($resultOrderlines)->sortBy($order_by)->reverse()->toArray();
        $keys = array_column($resultOrderlines, $order_by);
        array_multisort($keys, SORT_DESC, $resultOrderlines);

        if (count($array) > 0) {
            return response()->json([
                "success" => true,
                "message" => "Data found.",
                "data" => $resultOrderlines,
                // "Totalresult" => $totalResult
            ]);
        } else {
            return response()->json([
                "success" => FALSE,
                "message" => "Data Not found.",
            ]);
        }
    }

    public function DashboardMarginDay(Request $request)
    {
        $startDate = $request->input('startDate');
        $endDate = $request->input('endDate');
        $dataset  = [
            'labels' => array(),
            'datasets' => [
                'dataTotal' => [
                    'data' => array(),
                    'label' => array()

                ]
            ]
        ];
        $period = CarbonPeriod::create($startDate, '1 day', $endDate);
        $total = null;
        foreach ($period as $date) {
            $result = DB::table('orders')
                ->select('created_datetime', DB::raw('count(order_number) as bill_count'), DB::raw("SUM(total_margin) as sumtotal"), DB::raw("DATE(created_datetime) newday"))
                ->where("orders.active", 1)->where('orders.created_datetime',  '>=', $date->format('Y-m-d H:i:s'))
                ->where('orders.created_datetime', '<=', $date->endOfday()->format('Y-m-d H:i:s'))
                ->orderBy('created_datetime')
                ->groupby('newday')
                ->get();
            array_push($dataset['labels'], $date->format('Y-m-d'));
            if (!$result->isEmpty()) {
                $total += $result[0]->sumtotal;
                array_push($dataset['datasets']["dataTotal"]["data"],  floor($result[0]->sumtotal * 100) / 100);
            } else {
                array_push($dataset['datasets']["dataTotal"]["data"], 0);
            }
        }
        array_push($dataset['datasets']["dataTotal"]["label"], "กำไร");
        $dataset['total'] = floor($total * 100) / 100;

        return response()->json([
            "success" => true,
            "message" => "Data found.",
            "data" => $dataset
        ]);
    }
    public function DashboardMarginDayTable(Request $request)
    {
        $startDate = $request->input('startDate');
        $endDate = $request->input('endDate');
        $dataset = [];
        $total = null;
        $period = CarbonPeriod::create($startDate, '1 day', $endDate);
        foreach ($period as $date) {
            $result = DB::table('orders')
                ->select('created_datetime', DB::raw('count(order_number) as bill_count'), DB::raw("SUM(total_margin) as sumtotal"), DB::raw("DATE(created_datetime) newday"))
                ->where("orders.active", 1)->where('orders.created_datetime',  '>=', $date->format('Y-m-d H:i:s'))
                ->where('orders.created_datetime', '<=', $date->endOfday()->format('Y-m-d H:i:s'))
                ->orderBy('created_datetime')
                ->groupby('newday')
                ->get();

            if (!$result->isEmpty()) {
                $total += $result[0]->sumtotal;

                $data["new_date"] =   $date->format('Y-m-d');
                $data["count"] =   $result[0]->bill_count;
                $data["sumtotal"] =   floor($result[0]->sumtotal * 100) / 100;
                array_push($dataset, $data);
            } else {
                $data["new_date"] =   $date->format('Y-m-d');
                $data["count"] =   0;
                $data["sumtotal"] =    0;
                $total += 0;
                array_push($dataset, $data);
            }
        }
        if (!$dataset == []) {
            return response()->json([
                "success" => true,
                "message" => "Data found.",
                "data" => $dataset,
                "Totalresult" => floor($total * 100) / 100
            ]);
        } else {
            return response()->json([
                "success" => FALSE,
                "message" => "Data Not found.",
            ]);
        }
    }

    public function DashboardBill(Request $request)
    {
        $StartDateTime = $request->input('startDate');
        $EndDateTime = $request->input('endDate');
        $EndDateTime .= " 23:59";
        $StartDateTime .= " 00:00";
        $dataset = [];
        $order =
            OrderReources::collection(Orders::where("active", 1)->where('created_datetime',  '>=', $StartDateTime)
                ->where('created_datetime', '<=', $EndDateTime)->orderByDesc('created_datetime')->get());

        $total = DB::table('orders')
            ->select(DB::raw("SUM(payment_amount) as sumtotal"))->where('created_datetime',  '>=', $StartDateTime)
            ->where('created_datetime', '<=', $EndDateTime)
            ->first();

        foreach ($order as $data) {
            if ($data->member == null) {
                $obj["member"] =  "";
            } else if (!$data->member == null) {
                $obj["member"] =  $data->member->Nickname;
            }
            $obj["order_number"] =  $data->order_number;
            $obj["payment_amount"] =  $data->payment_amount;
            $obj["vat"] =  $data->vat;
            $obj["price_change"] =  $data->price_change;
            $obj["created_datetime"] =  $data->created_datetime;
            $obj["name"] =  $data->user->name;
            $obj["product"] = $data->orderline;
            $obj["payment"] = $data->orderpayment;
            array_push($dataset, $obj);
        }
        return response()->json([
            "success" => true,
            "message" => "Data found.",
            "data" => $dataset,
            "totalResult" => $total,
        ]);
    }
    public function DashboardBillTable(Request $request)
    {
        $StartDateTime = $request->input('startDate');
        $EndDateTime = $request->input('endDate');
        $EndDateTime .= " 23:59";
        $StartDateTime .= " 00:00";
        $dataset = [];
        $order =
            OrderReources::collection(Orders::where("active", 1)->where('created_datetime',  '>=', $StartDateTime)
                ->where('created_datetime', '<=', $EndDateTime)->orderByDesc('created_datetime')->get());

        $total = DB::table('orders')
            ->select(DB::raw("SUM(payment_amount) as sumtotal"))->where('created_datetime',  '>=', $StartDateTime)
            ->where('created_datetime', '<=', $EndDateTime)
            ->first();
        $count_qty = 0;
        foreach ($order as $data) {
            foreach ($data->orderline as $data_qty) {
                $count_qty += $data_qty->qty;
            }
            $obj["order_number"] =  $data->order_number;
            $obj["name"] =  $data->user->name;
            if ($data->member == null) {
                $obj["member"] =  "";
            } else if (!$data->member == null) {
                $obj["member"] =  $data->member->Nickname;
            }
            $obj["created_datetime"] =  $data->created_datetime;
            $obj["count"] = $count_qty;
            $obj["subtotal"] =  $data->subtotal;
            $obj["vat"] =  $data->vat;
            $obj["total"] = $data->total;
            array_push($dataset, $obj);
        }

        return response()->json([
            "success" => true,
            "message" => "Data found.",
            "data" => $dataset,
            "totalResult" => $total,
        ]);
    }

    public function DashboardSession(Request $request)
    {
        $StartDateTime = $request->input('startDate');
        $EndDateTime = $request->input('endDate');
        $new = array();
        $datasetDetail = [];
        $total = null;
        $EndDateTime .= " 23:59";
        $StartDateTime .= " 00:00";
        $result = DB::table('pos_session')
            ->leftJoin('users', 'pos_session.user_id', '=', 'users.id')
            ->leftJoin('orders', 'pos_session.pos_session_id', '=', 'orders.pos_session_id')
            ->select(
                'pos_session.pos_session_id',
                'pos_session.pos_session_name',
                'pos_session.open_datetime',
                'pos_session.close_datetime',
                'users.name',
                'pos_session.close_cash_amount',
                'pos_session.open_cash_amount',
                DB::raw('count(orders.order_number) as bill_count'),
                DB::raw("IFNULL(SUM(orders.subtotal),0) as sumtotal"),
                DB::raw("IFNULL(SUM(orders.price_change),0) as sumPriceChange"),
            )
            ->where('open_datetime',  '>=', $StartDateTime)
            ->where('open_datetime', '<=', $EndDateTime)
            ->groupby('pos_session.pos_session_id')
            ->orderBy('pos_session.open_datetime')
            ->get();
        $i = 0;
        $total = 0;
        foreach ($result as $value) {
            $data = DB::table('orders')
                ->leftJoin('order_payment', 'orders.order_id', '=', 'order_payment.order_id')
                ->select(
                    DB::raw("IFNULL(SUM(order_payment.amount),0) as sumAmount"),
                )
                ->where("orders.pos_session_id", $value->pos_session_id)
                ->where("order_payment.payment_id", 1)
                ->first();
            $obj["pos_session_id"] = $value->pos_session_id;
            $obj["pos_session_name"] = $value->pos_session_name;
            $obj["open_datetime"] = $value->open_datetime;
            $obj["name"] = $value->name;
            $obj["close_cash_amount"] = $value->close_cash_amount;
            $obj["open_cash_amount"] = $value->open_cash_amount;
            $obj["bill_count"] = $value->bill_count;
            $obj["sumtotal"] = $value->sumtotal;
            $obj["sumPriceChange"] = $value->sumPriceChange;
            $obj["sumAmount"] = $data->sumAmount;
            array_push($new, $obj);
        }
        foreach ($new as $results) {
            $new[$i]["difference"] =  ($results["close_cash_amount"]) - ($results["sumAmount"] + $results["open_cash_amount"]);
            $total += $results["sumtotal"];
            $i++;
        }
        return response()->json([
            "success" => true,
            "message" => "Data found.",
            "data" => $new,
            "total" => $total
        ]);
    }
    public function DashboardDetailSession($id)
    {

        $datasetDetailOrder = [];
        $datasetDetailPayment = [];
        $dataset = [];
        $dataSession = auth()->user();
        $resultPos = DB::table('pos_session')
            ->join('users', 'pos_session.user_id', '=', 'users.id')
            ->select(
                'pos_session.pos_session_id',
                'pos_session.pos_session_name',
                'pos_session.open_datetime',
                'pos_session.close_datetime',
                'pos_session.close_cash_amount',
                'pos_session.open_cash_amount',
                'users.name'
            )->where('pos_session.pos_session_id',  $id)->get();

        for ($i = 0; $i < $resultPos->count(); $i++) {

            $data["pos_session_id"] =   $resultPos[$i]->pos_session_id;
            $data["pos_session_name"] =   $resultPos[$i]->pos_session_name;
            $data["open_datetime"] =   $resultPos[$i]->open_datetime;
            $data["close_datetime"] =   $resultPos[$i]->close_datetime;
            $data["close_cash_amount"] =   $resultPos[$i]->close_cash_amount;
            $data["open_cash_amount"] =   $resultPos[$i]->open_cash_amount;
            $data["name"] =  $resultPos[$i]->name;
            array_push($dataset, $data);
        }
        if ($resultPos->isEmpty()) {
            return response()->json([
                "success" => false,
                "message" => "not have data session"
            ]);
        }
        $resultTotal = DB::table('orders')->select(DB::raw("SUM(subtotal) as sumtotal"), DB::raw("SUM(vat) as vat"), DB::raw("SUM(price_change) as price_change"))
            ->where("active", 1)->where('pos_session_id',  $resultPos[0]->pos_session_id)->groupby('pos_session_id')->get();


        $orderlines = Orders::with('orderline')->select('order_id')->where("pos_session_id", $resultPos[0]->pos_session_id)->get();
        for ($i = 0; $i < $orderlines->count(); $i++) {
            $count = $orderlines[$i]['orderline']->count();
            for ($j = 0; $j < $count; $j++) {
                $dataDetailOrder = null;
                $dataDetailOrder["product_id"] =   $orderlines[$i]['orderline'][$j]["product_id"];
                $dataDetailOrder["price"] =   $orderlines[$i]['orderline'][$j]["price"];
                $dataDetailOrder["qty"] =   $orderlines[$i]['orderline'][$j]["qty"];
                $dataDetailOrder["sum_qty"] =   0;
                $dataDetailOrder["product_name"] =   $orderlines[$i]['orderline'][$j]["product"]->product_name;
                if ($orderlines[$i]['orderline'][$j]["product_uom_id"]) {
                    $dataDetailOrder["product_uom_id"] =   $orderlines[$i]['orderline'][$j]["product_uom_id"];
                    $dataDetailOrder["product_uom_name"] =   $orderlines[$i]['orderline'][$j]["uom"]->product_uom_name;
                    $dataDetailOrder["multiple_qty"] =   $orderlines[$i]['orderline'][$j]["uom"]->multiple_qty;
                    // var_dump($orderlines[$i]['orderline'][$j]["qty"]);
                    // $dataDetailOrder["qty"]
                    // = $orderlines[$i]['orderline'][$j]["qty"]
                    // + $orderlines[$i]['orderline'][$j]["uom"]->multiple_qty;
                    // var_dump($dataDetailOrder["qty"]);
                }
                array_push($datasetDetailOrder, $dataDetailOrder);
            }
        }
        $n = 0;

        $issetArray = array();
        // var_dump($datasetDetailOrder);
        foreach ($datasetDetailOrder as $key => $value) {
            if (isset($issetArray[$value['product_id']]) != isset($value['product_id'])) {
                if (isset($value['product_uom_id'])) {
                    $value['sum_qty'] += $value['qty'] * $value['multiple_qty'];
                    $value['qty'] = $value['sum_qty'];
                    $value['price'] = $value['price'] * $value['qty'];
                    $issetArray[$value['product_id']] = array();
                    $issetArray[$value['product_id']] = $value;
                } else {
                    $value['sum_qty'] += $value['qty'];
                    $value['qty'] = $value['sum_qty'];

                    $issetArray[$value['product_id']] = array();
                    $issetArray[$value['product_id']] = $value;
                }
            } else {


                if (isset($value['product_uom_id'])) {

                    $issetArray[$value['product_id']]['sum_qty'] += $value['qty'] * $value['multiple_qty'];
                    $issetArray[$value['product_id']]['qty'] = $issetArray[$value['product_id']]['sum_qty'];
                    $issetArray[$value['product_id']]['price'] += $value['price'] * $value['qty'];
                } else if (isset($value['product_id'])) {
                    // $value['sum_qty'] += $value['qty'];
                    // $value['sum_qty'] += $value['qty'];
                    // $value['price'] = $value['price'] * $value['qty'];
                    $issetArray[$value['product_id']]['sum_qty'] += $value['qty'];
                    $issetArray[$value['product_id']]['qty'] = $issetArray[$value['product_id']]['sum_qty'];

                    $issetArray[$value['product_id']]['price'] += $value['price'] * $value['qty'];
                } else {
                    $value['sum_qty'] += $value['qty'];

                    $issetArray[$value['product_id']] = array();
                    $issetArray[$value['product_id']] = $value;
                }
            }
            $n++;
        }

        $resultOrderlines = array();
        foreach ($issetArray as $value) {
            // $resultOrderlines["total"] = $resultTotal;

            array_push($resultOrderlines, $value);
        }
        $order = Orders::with('orderpayment')->select('order_id')->where("pos_session_id", $resultPos[0]->pos_session_id)->get();
        for ($i = 0; $i < $order->count(); $i++) {
            $count = $order[$i]['orderpayment']->count();
            for ($j = 0; $j < $count; $j++) {

                $dataDetail["payment_id"] =   $order[$i]['orderpayment'][$j]["payment_id"];
                $dataDetail["amount"] =   $order[$i]['orderpayment'][$j]["amount"];
                $dataDetail["payment_name"] =   $order[$i]['orderpayment'][$j]["payment"]->payment_name;

                array_push($datasetDetailPayment, $dataDetail);
            }
        }
        $issetArrayPay = array();

        foreach ($datasetDetailPayment as $key => $values) {
            if (isset($issetArrayPay[$values['payment_id']])) {
                $issetArrayPay[$values['payment_id']]['amount'] += $values['amount'];
            } else {
                $issetArrayPay[$values['payment_id']] = array();
                $issetArrayPay[$values['payment_id']] = $values;
            }
        }

        $resultPay = array();
        foreach ($issetArrayPay as $values) {
            if ($values["payment_id"] == 1) {
                $values["amount"] =  $values["amount"];
            }
            array_push($resultPay, $values);
        }
        return response()->json([
            "success" => true,
            "message" =>
            "Session Detail",
            "dataSession" => $dataset,
            "dataDetailProduct" => $resultOrderlines,
            "totalResult" => $resultTotal,
            // "vatResult" => $resultVatTotal,
            "dataDetail" => $resultPay
        ]);
    }

    //     return response()->json([
    //         "success" => true,
    //         "message" => "Session Detail",
    //         "dataSession" => $resultPos,
    //         "dataDetail" => $result,
    //         "dataDetailProduct" => $resultProduct
    //     ]);
    // public function DashboardDetailSession($id)
    // {
    //     $resultProduct = array();
    //     $datasetDetail = [];
    //     $dataset = [];
    //     $resultPos = DB::table('pos_session')
    //         ->join('users', 'pos_session.user_id', '=', 'users.id')
    //         ->join('orders', 'pos_session.pos_session_id', '=', 'orders.pos_session_id')
    //         ->join('order_payment', 'orders.order_id', '=', 'order_payment.order_id')
    //         ->select(
    //             'pos_session.pos_session_id',
    //             'pos_session.pos_session_name',
    //             'pos_session.open_datetime',
    //             'pos_session.close_datetime',
    //             'users.name',
    //             'pos_session.close_cash_amount',
    //             'pos_session.open_cash_amount',
    //             'pos_session.active',
    //             DB::raw('count(orders.order_number) as bill_count'),
    //             DB::raw("IFNULL(SUM(orders.total),0) as sumtotal"),
    //             DB::raw("SUM(orders.price_change) as sumPriceChange"),
    //             DB::raw("SUM(order_payment.amount) as sumAmount")
    //         )
    //         ->where("order_payment.amount", "!=", 0)
    //         // ->where("order_payment.payment_id", "=", 1)
    //         ->where('orders.pos_session_id',  $id)
    //         ->groupby('orders.pos_session_id')
    //         ->orderBy('pos_session.open_datetime')
    //         ->get();
    //     // var_dump($resultPos[0]->sumtotal);

    //     $order = Orders::with('orderpayment')->select('order_id')->where("pos_session_id", $id)->get();
    //     for ($i = 0; $i < $order->count(); $i++) {
    //         $count = $order[$i]['orderpayment']->count();
    //         for ($j = 0; $j < $count; $j++) {
    //             $dataDetail["payment_id"] =   $order[$i]['orderpayment'][$j]["payment_id"];
    //             $dataDetail["amount"] =   $order[$i]['orderpayment'][$j]["amount"];
    //             $dataDetail["payment_name"] =   $order[$i]['orderpayment'][$j]["payment"]->payment_name;
    //             array_push($datasetDetail, $dataDetail);
    //         }
    //     }
    //     $issetArray = array();
    //     foreach ($datasetDetail as $key => $value) {
    //         if (isset($issetArray[$value['payment_id']])) {        //วนลูปบวก ค่าที่ไอดีเดียวกัน
    //             $issetArray[$value['payment_id']]['amount'] += $value['amount'];
    //         } else {
    //             $issetArray[$value['payment_id']] = array();
    //             $issetArray[$value['payment_id']] = $value;
    //         }
    //     }
    //     $result = array();
    //     foreach ($issetArray as $values) {
    //         if ($values['payment_id'] == 1) {
    //             $values["amount"] =  $values["amount"];
    //         }
    //         array_push($result, $values);
    //     }
    //     $resultProductId = array();
    //     $n = 0;

    //     $orders = Orders::select('order_id')->where("pos_session_id",  $id)->get();
    //     if ($orders->count() >= 1) {
    //         foreach ($orders as $order) {
    //             // var_dump($n);
    //             $datas = Order_lines::select('order_line_id', 'product_id', 'product_uom_id', 'qty')->where('active', 1)->where('order_id', $order->order_id)->get();
    //             $dataProduct = array();

    //             foreach ($datas as  $data) {
    //                 $dataProduct[$data['product_id']] = array();
    //                 $dataProduct[$data['product_id']] = $data;
    //                 if (isset($data['product_uom_id'])) {        //วนลูปบวก ค่าที่ไอดีเดียวกัน
    //                     // var_dump($data['product_uom_id']);
    //                     $dataProductUOM = Product_uom::select('multiple_qty')->where('product_uom_id', $data['product_uom_id'])->first();
    //                     // var_dump($dataProduct[$data['product_id']]['qty']);
    //                     $dataProduct[$data['product_id']]['qty'] += $data['qty'] * $dataProductUOM->multiple_qty;
    //                 } else if (isset($data['product_id'])) {
    //                     $dataProduct[$data['product_id']]['qty'] += $data['qty'];
    //                 } else {
    //                     $dataProduct[$data['product_id']] = array();
    //                     $dataProduct[$data['product_id']] = $data;
    //                 }
    //             }
    //             $n += 1;
    //         }
    //         foreach ($dataProduct as $dataProducts) {
    //             $dataProtest = Product::select('product_name')->where('product_id', $dataProducts["product_id"])->first();
    //             if (!$dataProtest == []) {
    //                 $dataP["product_name"] = $dataProtest->product_name;
    //                 $dataP["qty"] = $dataProducts->qty;
    //                 array_push($resultProduct, $dataP);
    //             }
    //         }
    //     }
    //     if ($result[0]["amount"]) {
    //         if (!$resultPos->isEmpty()) {
    //             $resultPos[0]->difference = ($resultPos[0]->close_cash_amount) - ($result[0]["amount"] + $resultPos[0]->open_cash_amount);
    //             $resultPos[0]->sumAmount = $resultPos[0]->sumAmount;
    //         }
    //     } else {
    //         if (!$resultPos->isEmpty()) {
    //             $resultPos[0]->difference = ($resultPos[0]->close_cash_amount -  $resultPos[0]->open_cash_amount);

    //             $resultPos[0]->sumAmount = $resultPos[0]->sumAmount;
    //         }
    //     }
    //     return response()->json([
    //         "success" => true,
    //         "message" => "Session Detail",
    //         "dataSession" => $resultPos,
    //         "dataDetail" => $result,
    //         "dataDetailProduct" => $resultProduct
    //     ]);
    // }
    public function DashboardOutOfStockTable(Request $request)
    {
        $dataset = [];
        $datas = Out_of_stock::with('product')->where('active', 1)->get();
        foreach ($datas as $data) {
            $dataProduct = Product::select('product_name', 'stock_qty')->where('product_id', $data->product_id)
                ->where('stock_qty', '<=', $data->out_of_stock_qty)
                ->first();
            if (!$dataProduct == []) {
                $obj["product_name"] =  $dataProduct->product_name;
                $obj["stock_qty"] =  $dataProduct->stock_qty;
                $obj["out_of_stock_qty"] =  $data->out_of_stock_qty;
                array_push($dataset, $obj);
            }
        }
        return response()->json([
            "success" => true,
            "message" => "Data found.",
            "data" => $dataset,
        ]);
    }
    public function DashboardSessionBranch(Request $request)
    {
        $StartDateTime = $request->input('startDate');
        $EndDateTime = $request->input('endDate');
        $new = array();
        $datasetDetail = [];
        $total = null;
        $EndDateTime .= " 23:59";
        $StartDateTime .= " 00:00";
        $result = DB::table('pos_session')
            ->leftJoin('users', 'pos_session.user_id', '=', 'users.id')
            ->leftJoin('orders', 'pos_session.pos_session_id', '=', 'orders.pos_session_id')
            ->select(
                'pos_session.pos_session_id',
                'pos_session.pos_session_name',
                'pos_session.open_datetime',
                'pos_session.close_datetime',
                'users.name',
                'pos_session.close_cash_amount',
                'pos_session.open_cash_amount',
                DB::raw('count(orders.order_number) as bill_count'),
                DB::raw("IFNULL(SUM(orders.subtotal),0) as sumtotal"),
                DB::raw("IFNULL(SUM(orders.price_change),0) as sumPriceChange"),
            )
            ->where('open_datetime',  '>=', $StartDateTime)
            ->where('open_datetime', '<=', $EndDateTime)
            ->groupby('pos_session.pos_session_id')
            ->orderBy('pos_session.open_datetime')
            ->get();
        $i = 0;
        $total = 0;
        foreach ($result as $value) {
            $data = DB::table('orders')
                ->leftJoin('order_payment', 'orders.order_id', '=', 'order_payment.order_id')
                ->select(
                    DB::raw("IFNULL(SUM(order_payment.amount),0) as sumAmount"),
                )
                ->where("orders.pos_session_id", $value->pos_session_id)
                ->where("order_payment.payment_id", 1)
                ->first();
            $obj["pos_session_id"] = $value->pos_session_id;
            $obj["pos_session_name"] = $value->pos_session_name;
            $obj["open_datetime"] = $value->open_datetime;
            $obj["close_datetime"] = $value->close_datetime;
            $obj["name"] = $value->name;
            $obj["close_cash_amount"] = $value->close_cash_amount;
            $obj["open_cash_amount"] = $value->open_cash_amount;
            $obj["bill_count"] = $value->bill_count;
            $obj["sumtotal"] = $value->sumtotal;
            $obj["sumPriceChange"] = $value->sumPriceChange;
            $obj["sumAmount"] = $data->sumAmount;
            array_push($new, $obj);
        }
        foreach ($new as $results) {
            $new[$i]["difference"] =  ($results["close_cash_amount"]) - ($results["sumAmount"] + $results["open_cash_amount"]);
            $total += $results["sumtotal"];
            $i++;
        }
        return response()->json([
            "success" => true,
            "message" => "Data found.",
            "data" => $new,
            "total" => $total
        ]);
    }
    public function DashboardOrderDayBranch(Request $request)
    {
        $startDate = $request->input('startDate');
        $endDate = $request->input('endDate');
        $dataset = [];
        $total = null;
        $period = CarbonPeriod::create($startDate, '1 day', $endDate);
        foreach ($period as $date) {
            $result = DB::table('orders')
                ->select('created_datetime', DB::raw("SUM(subtotal) as sumsubtotal"), DB::raw('count(order_number) as bill_count'), DB::raw("SUM(total) as sumtotal"),  DB::raw("SUM(total_margin) as sumtotalmargin"), DB::raw("DATE(created_datetime) newday"))
                ->where("orders.active", 1)->where("orders.type", '!=', 2)->where('orders.created_datetime',  '>=', $date->format('Y-m-d H:i:s'))
                ->where('orders.created_datetime', '<=', $date->endOfday()->format('Y-m-d H:i:s'))
                ->orderBy('created_datetime')
                ->groupby('newday')
                ->get();

            if (!$result->isEmpty()) {
                $total += $result[0]->sumtotal;
                $data["cost"] =   floor(($result[0]->sumsubtotal - $result[0]->sumtotalmargin) * 100) / 100;

                $data["new_date"] =   $date->format('Y-m-d');
                $data["count"] =   $result[0]->bill_count;
                $data["sumtotal"] =   floor($result[0]->sumtotal * 100) / 100;
                $data["sumtotalmargin"] =   floor($result[0]->sumtotalmargin * 100) / 100;
                array_push($dataset, $data);
            } else {
                $data["new_date"] =   $date->format('Y-m-d');
                $data["count"] =   0;
                $data["sumtotal"] =    0;
                $data["cost"] =  0;
                $data["sumtotalmargin"] =    0;
                $total += 0;
                array_push($dataset, $data);
            }
        }

        if (!$dataset == []) {
            return response()->json([
                "success" => true,
                "message" => "Data found.",
                "data" => $dataset,
                "Totalresult" => floor($total * 100) / 100
            ]);
        } else {
            return response()->json([
                "success" => FALSE,
                "message" => "Data Not found.",
            ]);
        }
    }

    public function DashboardDailySale(Request $request)
    {
        $startDate = $request->input('startDate');
        $endDate = $request->input('endDate');
        $dataset = [];
        $total = null;
        $period = CarbonPeriod::create($startDate, '1 day', $endDate);
        foreach ($period as $date) {
            $result = DB::table('orders')
                ->select('created_datetime', DB::raw("SUM(subtotal) as sumsubtotal"), DB::raw('count(order_number) as bill_count'), DB::raw("SUM(total) as sumtotal"),  DB::raw("SUM(total_margin) as sumtotalmargin"), DB::raw("DATE(created_datetime) newday"))
                ->where("orders.active", 1)->where("orders.type", '!=', 2)->where('orders.created_datetime',  '>=', $date->format('Y-m-d H:i:s'))
                ->where('orders.created_datetime', '<=', $date->endOfday()->format('Y-m-d H:i:s'))
                ->orderBy('created_datetime')
                ->groupby('newday')
                ->get();
            if (!$result->isEmpty()) {
                $total += $result[0]->sumtotal;
                $data["sumtotal"] =   floor($result[0]->sumtotal * 100) / 100;
                $data["new_date"] =   $date->format('Y-m-d');
                array_push($dataset, $data);
            } else {
                $data["new_date"] =   $date->format('Y-m-d');
                $data["sumtotal"] =    0;
                $total += 0;
                array_push($dataset, $data);
            }
        }

        if (!$dataset == []) {
            return response()->json([
                "success" => true,
                "message" => "Data found.",
                "data" => $dataset,
                "Totalresult" => floor($total * 100) / 100
            ]);
        } else {
            return response()->json([
                "success" => FALSE,
                "message" => "Data Not found.",
            ]);
        }
    }
    public function DashboardDailySaleChart(Request $request)
    {

        $startDate = $request->input('startDate');
        $endDate = $request->input('endDate');
        $dataset  = [
            'labels' => array(),
            'datasets' => [
                'dataTotal' => [
                    'data' => array(),
                    'label' => array()

                ]
            ]
        ];
        $period = CarbonPeriod::create($startDate, '1 day', $endDate);
        $total = null;
        $totalcost = null;
        foreach ($period as $date) {
            $result = DB::table('orders')
                ->select('created_datetime',  DB::raw("SUM(subtotal) as sumsubtotal"), DB::raw('count(order_number) as bill_count'), DB::raw("SUM(total) as sumtotal"), DB::raw("SUM(total_margin) as sumtotalmargin"), DB::raw("DATE(created_datetime) newday"))
                ->where("orders.active", 1)->where("orders.type", '!=', 2)->where('orders.created_datetime',  '>=', $date->format('Y-m-d H:i:s'))
                ->where('orders.created_datetime', '<=', $date->endOfday()->format('Y-m-d H:i:s'))
                ->orderBy('created_datetime')
                ->groupby('newday')
                ->get();
            array_push($dataset['labels'], $date->format('Y-m-d'));

            if (!$result->isEmpty()) {
                $total += $result[0]->sumtotal;
                array_push($dataset['datasets']["dataTotal"]["data"], floor($result[0]->sumtotal * 100) / 100);
            } else {
                array_push($dataset['datasets']["dataTotal"]["data"], 0);
            }
        }
        array_push($dataset['datasets']["dataTotal"]["label"], "ยอดขายรายวัน");
        $dataset['total'] = $total;

        return response()->json([
            "success" => true,
            "message" => "Data found.",
            "data" => $dataset
        ]);
    }
}
