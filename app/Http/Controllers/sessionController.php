<?php

namespace App\Http\Controllers;


use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;
use App\Http\Resources\OrderReources;
use App\Models\PosSession;
use App\Models\Orders;
use App\Models\Out_of_stock;
use App\Models\Product;
// use Phattarachai\LineNotify\Facade\Line;
use Phattarachai\LineNotify\Line;
use App\Models\MetaData;
use App\Models\Order_lines;

use function PHPUnit\Framework\isEmpty;

class sessionController extends Controller
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
    public function checkSession()
    {
        $dataSession = auth()->user();
        $data = PosSession::select('*')->where('user_id', $dataSession->id)->where('close_datetime', null)->where('close_cash_amount', null)->where('active', 1)->get();
        if ($data->isEmpty()) {
            return response()->json([
                "success" => false,
                "message" => "not have data",
            ]);
            // die;

        } elseif ($data->count() >= 1) {
            return response()->json([
                "success" => true,
                "message" => "open session",
            ]);
        } else {
            return response()->json([
                "success" => false,
                "message" => "close session",
            ]);
        }
    }

    public function open(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'open_cash_amount' => 'required|gte:0',

        ]);
        if ($validator->fails()) {
            return response()->json([
                "status" => false,
                "messageError" => $validator->errors()->toJson()
            ], 400);
        }
        $dataOpenSession = MetaData::select('meta_value')->where("active", 1)->where("meta_module", 'printer')->where("meta_key", 'mode_print')->first();
        $dataCountcashier = MetaData::select('meta_value')->where("active", 1)->where("meta_module", 'printer')->where("meta_key", 'count_cashier')->first();
        $dataCountcashierInt = (int)env('COUNT_CASHIER');
        $dataSession = auth()->user();
        $datetimecurrent = $this->datetimecurrent();
        $yearcurrent = Carbon::now()->timezone('Asia/Bangkok')->format("Y");
        $open_cash_amount = $request->input('open_cash_amount');
        $pos_name = '';
        $pos_name_data = PosSession::select('*')->where('pos_session_name', 'LIKE', "%$yearcurrent%")->count();
        $pos_name_data = $pos_name_data + 1;
        $pos_name .= $yearcurrent;
        $pos_name .= "/";
        $pos_name .= $pos_name_data;
        $data = PosSession::where('user_id', $dataSession->id)->orderBy('open_datetime', 'desc')->get();
        if ($dataOpenSession->meta_value == 2) {
            $data = PosSession::where('active', 1)->orderBy('open_datetime', 'desc')->get();
            if ($data->count() < $dataCountcashierInt) {
                PosSession::insert([
                    'pos_session_name' => $pos_name,
                    'user_id' => $dataSession->id,
                    'open_datetime' => $datetimecurrent,
                    "open_cash_amount" => $open_cash_amount
                ]);
                return response()->json([
                    "success" => true,
                    "message" => "session open successfully",
                ]);
            } else {
                return response()->json([
                    "success" => false,
                    "message" => "ไม่สามารถเปิดกะได้ เนื่องจากมีผู้ใช้คนอื่นกำลังทำการขายสินค้าอยู่ ณ ขณะนี้",
                ]);
            }
        }
        if ($data->count() == 0) {
            $insert = PosSession::insert([
                'pos_session_name' => $pos_name,
                'user_id' => $dataSession->id,
                'open_datetime' => $datetimecurrent,
                "open_cash_amount" => $open_cash_amount
            ]);
            return response()->json([
                "success" => true,
                "message" => "session open successfully new",
            ]);
        } elseif ($data[0]->open_datetime && $data[0]->close_datetime == null  && $data[0]->open_cash_amount >= 0 && $data[0]->close_cash_amount == null) {

            return response()->json([
                "success" => false,
                "message" => "session open now.",
            ]);
        } else {

            $insert = PosSession::insert([
                'pos_session_name' => $pos_name,
                'user_id' => $dataSession->id,
                'open_datetime' => $datetimecurrent,
                "open_cash_amount" => $open_cash_amount
            ]);
            return response()->json([
                "success" => true,
                "message" => "session open successfully.",
            ]);
        }
    }

    public function close(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'close_cash_amount' => 'required|gte:0',

        ]);
        if ($validator->fails()) {
            return response()->json([
                "status" => false,
                "messageError" => $validator->errors()->toJson()
            ], 400);
        }
        $dataSession = auth()->user();
        $datetimecurrentclose = $this->datetimecurrent();
        $close_cash_amount = $request->input('close_cash_amount');
        $dataPos = PosSession::where('user_id', $dataSession->id,)->where('active', 1)->first();
        $dataCount = PosSession::where('user_id', $dataSession->id,)->where('active', 1)->count();

        DB::beginTransaction();

        if ($dataCount == 1) {

            PosSession::where('user_id', $dataSession->id,)->where('active', 1)->update([
                'close_datetime' => $datetimecurrentclose,
                "close_cash_amount" => $close_cash_amount,
                "active" => 0
            ]);
            $dataLineToken = MetaData::select('meta_value')->where("active", 1)->where("meta_module", 'store')->where("meta_key", 'store_line_token')->first();
            $datasetDetail = [];

            $resultPos = DB::table('pos_session')
                ->join('users', 'pos_session.user_id', '=', 'users.id')
                ->join('orders', 'pos_session.pos_session_id', '=', 'orders.pos_session_id')
                ->join('order_payment', 'orders.order_id', '=', 'order_payment.order_id')
                ->select(
                    'pos_session.pos_session_id',
                    'pos_session.pos_session_name',
                    'pos_session.open_datetime',
                    'pos_session.close_datetime',
                    'users.name',
                    'pos_session.close_cash_amount',
                    'pos_session.open_cash_amount',
                    DB::raw('count(orders.order_number) as bill_count'),
                    DB::raw("SUM(orders.subtotal) as sumtotal"),
                    DB::raw("SUM(orders.price_change) as sumPriceChange"),
                    DB::raw("SUM(order_payment.amount) as sumAmount")
                )
                ->where("order_payment.payment_id", 1)
                ->where('orders.pos_session_id',  $dataPos->pos_session_id)
                ->groupby('orders.pos_session_id')
                ->orderBy('pos_session.open_datetime')
                ->get();
            if (!$resultPos->isEmpty()) {
                $resultPos[0]->sumAmount = $resultPos[0]->sumAmount - $resultPos[0]->sumPriceChange;
            }

            $order = Orders::with('orderpayment')->select('order_id')->where("pos_session_id",  $dataPos->pos_session_id)->get();
            for ($i = 0; $i < $order->count(); $i++) {
                $count = $order[$i]['orderpayment']->count();
                for ($j = 0; $j < $count; $j++) {

                    $dataDetail["payment_id"] =   $order[$i]['orderpayment'][$j]["payment_id"];
                    $dataDetail["amount"] =   $order[$i]['orderpayment'][$j]["amount"];
                    $dataDetail["payment_name"] =   $order[$i]['orderpayment'][$j]["payment"]->payment_name;

                    array_push($datasetDetail, $dataDetail);
                }
            }
            $issetArray = array();
            foreach ($datasetDetail as $key => $value) {
                if (isset($issetArray[$value['payment_id']])) {        //วนลูปบวก ค่าที่ไอดีเดียวกัน
                    $issetArray[$value['payment_id']]['amount'] += $value['amount'];
                } else {
                    $issetArray[$value['payment_id']] = array();
                    $issetArray[$value['payment_id']] = $value;
                }
            }
            $result = array();
            foreach ($issetArray as $values) {
                if ($values['payment_id'] == 1) {

                    $values["amount"] =  $values["amount"];
                }
                array_push($result, $values);
            }

            if (!$dataLineToken["meta_value"] == NULL) {
                $line = new Line($dataLineToken["meta_value"]);
                $text = "\nพนักงาน : $dataSession->name \nชื่อกะ : $dataPos->pos_session_name \nเวลาเปิดกะ : $dataPos->open_datetime\nเวลาปิดกะ : $datetimecurrentclose\nยอดเปิดกะ : $dataPos->open_cash_amount\nยอดปิดกะ : $close_cash_amount";
                foreach ($result as $results) {
                    $pay_name = $results["payment_name"];
                    $pay_amount = $results["amount"];
                    $text .= "\n$pay_name : $pay_amount";
                }
                $orders = Orders::select('order_id')->where("pos_session_id",  $dataPos->pos_session_id)->get();
                foreach ($orders as $order) {
                    $datas = Order_lines::select('order_line_id', 'product_id')->where('active', 1)->where('order_id', $order->order_id)->get();
                    $sss = array();
                    foreach ($datas as  $data) {
                        if (isset($dataProduct[$data['product_id']])) {        //วนลูปบวก ค่าที่ไอดีเดียวกัน
                            $dataProduct[$data['product_id']]['product_id'] = $data['product_id'];
                        } else {
                            $dataProduct[$data['product_id']] = array();
                            $dataProduct[$data['product_id']] = $data;
                        }
                    }

                    $resultPay = array();
                    foreach ($dataProduct as $data) {
                        $mapdata["product_id"] = $data->product_id;
                        array_push($resultPay, $mapdata);
                    }
                }
                $line->send($text);
                $data = DB::select("SELECT p.product_name,p.stock_qty FROM product p INNER JOIN out_of_stock o ON p.product_id=o.product_id where p.stock_qty<o.out_of_stock_qty  AND  o.active=1;");
                $chunks = array_chunk($data, 5);
                $count = count($chunks);
                for ($i = 0; $i < $count; $i++) {
                    $textChunk = "";
                    foreach ($chunks[$i] as $Product) {
                        $textChunk .= "\nสินค้าใกล้หมดสต๊อก : $Product->product_name คงเหลือ : $Product->stock_qty";
                    }
                    $line->send($textChunk);
                }
            }
            DB::commit();
            return response()->json([
                "success" => true,
                "message" => "session close successfully.",
                "pos_session_id" => $dataPos->pos_session_id
            ]);
        } else {
            DB::rollBack();
            return response()->json([
                "success" => false,
                "message" => "session close fail.",
            ]);
        }
    }

    // public function testchunk()
    // {
    //     $dataLineToken = MetaData::select('meta_value')->where("active", 1)->where("meta_module", 'store')->where("meta_key", 'store_line_token')->first();

    //     $line = new Line($dataLineToken["meta_value"]);

    //     $data = DB::select("SELECT p.product_name,p.stock_qty FROM product p INNER JOIN out_of_stock o ON p.product_id=o.product_id where p.stock_qty<o.out_of_stock_qty;");
    //     $chunks = array_chunk($data, 5);
    //     $count = count($chunks);
    //     for ($i = 0; $i < $count; $i++) {
    //         $textChunk = "";
    //         foreach ($chunks[$i] as $Product) {
    //             $textChunk .= "\nสินค้าใกล้หมดสต๊อก : $Product->product_name คงเหลือ : $Product->stock_qty";
    //         }
    //         $line->send($textChunk);
    //     }
    //     return response()->json([
    //         "success" => true,
    //         // "message" => count($chunks);
    //         "dataSession" => $text

    //     ]);
    // }
    public function checkBillLastSession()
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
                'users.name'
            )->where('pos_session.user_id',  $dataSession->id)->where('pos_session.active', 1)->get();

        for ($i = 0; $i < $resultPos->count(); $i++) {

            $data["pos_session_id"] =   $resultPos[$i]->pos_session_id;
            $data["pos_session_name"] =   $resultPos[$i]->pos_session_name;
            $data["open_datetime"] =   $resultPos[$i]->open_datetime;
            $data["close_datetime"] =   $resultPos[$i]->close_datetime;
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
            "message" => "data",
            "dataSession" => $dataset,
            "dataOrderline" => $resultOrderlines,
            "totalResult" => $resultTotal,
            // "vatResult" => $resultVatTotal,
            "dataPayment" => $resultPay
        ]);
    }
}
