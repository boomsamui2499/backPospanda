<?php

namespace App\Http\Controllers;

use App\Models\Member;
use App\Models\Orders;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;
use Order;

class MemberController extends Controller
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
        $data = Member::where('active', 1)->get();

        return response()->json([
            "success" => true,
            "message" => "member List",
            "data" => $data
        ]);
    }


    public function showFitterMember($id)
    {

        $data = Member::where('active', 1)->where('member_id', $id)->get();

        if ($id == 0) {
            $data = Member::where('active', 1)->get();
        }
        return response()->json([
            "success" => true,
            "message" => "Member List",
            "data" => $data
        ]);
    }

    public function add(Request $request)
    {
        $member_code = '';
        $member_code_count = DB::table('member')->select('*')->count();
        $member_code_count = $member_code_count + 1;
        $member_code .= "A00";
        $member_code .= $member_code_count;
        $registered_date = $this->datetimecurrent(); //datatimecurrent


        $validator = Validator::make($request->all(), [
            'Firstname' => 'string|between:2,100',
            'Lastname' => 'string|between:2,100',
            'phone_number' => 'string|between:2,100',
        ]);
        if ($validator->fails()) {
            return response()->json([
                "status" => false,
                "messageError" => $validator->errors()->toJson()
            ], 400);
        }
        $data = [
            'Firstname' => $request->input('Firstname'),
            'Lastname' => $request->input('Lastname'),
            'Nickname' => $request->input('Nickname'),
            'gender' => $request->input('gender'),
            'phone_number' => $request->input('phone_number'),
            'member_code' => $request->input('member_code'),
            'birthdate' => $request->input('birthdate'),
            "address_line1" => $request->input('address_line1'),
            "address_line2" => $request->input('address_line2'),
            "province" => $request->input('province'),
            "zip_code" => $request->input('zip_code'),
            "debt" => "0",
            "line_id" => $request->input('line_id'),
            "tax_registered_number" => $request->input('tax_registered_number'),
            "loyalty_point" => "0",
            "registered_date" => $registered_date
        ];
        $dataHavemembercode = [
            'Firstname' => $request->input('Firstname'),
            'Lastname' => $request->input('Lastname'),
            'Nickname' => $request->input('Nickname'),
            'gender' => $request->input('gender'),
            'phone_number' => $request->input('phone_number'),
            'member_code' => $member_code,
            'birthdate' => $request->input('birthdate'),
            "address_line1" => $request->input('address_line1'),
            "address_line2" => $request->input('address_line2'),
            "province" => $request->input('province'),
            "zip_code" => $request->input('zip_code'),
            "debt" => "0",
            "line_id" => $request->input('line_id'),
            "tax_registered_number" => $request->input('tax_registered_number'),
            "loyalty_point" => "0",
            "registered_date" => $registered_date
        ];
        if (!$request->input('member_code') == "") {
            $dataLinId = Member::select('*')->where('active', 1)->where('line_id', $request->input("line_id"))->get();
            if ($dataLinId->count() >= 1) {
                return response()->json([
                    "success" => FALSE,
                    "message" => "Line ID duplicate.",
                ]);
            }
        }
        if (!$request->input('member_code') == "") {
            $member_id = Member::insertGetId($data);
        } else {
            $member_id = Member::insertGetId($dataHavemembercode);
        }
        $member = Member::select('*')->where("member_id", $member_id)->get();
        if ($member->count() == 1) {
            return response()->json([
                "success" => true,
                "message" => "Member created successfully.",
                "member_id" => $member_id,
            ]);
        } else {
            return response()->json([
                "success" => false,
                "message" => "Member created fail.",
            ]);
        }
    }

    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'Firstname' => 'string|between:2,100',
            'Lastname' => 'string|between:2,100',
            'phone_number' => 'string|between:2,100',
        ]);
        if ($validator->fails()) {
            return response()->json([
                "status" => false,
                "messageError" => $validator->errors()->toJson()
            ], 400);
        }

        $data = Member::select('*')->where('line_id', $request->input("line_id"))->where('line_id', '!=', null)->where('member_id', '!=', $id)->get();
        $datamember_code = Member::select('*')->where('member_code', $request->input("member_code"))->where('member_id', '!=', $id)->get();
        if ($data->count() == 0 && $datamember_code->count() == 0) {
            Member::where('member_id', $id)->update($request->input());
            return response()->json([
                "success" => true,
                "message" => "member update successfully.",
            ]);
        } elseif ($data->count() >= 1 || $datamember_code->count() >= 1) {
            return response()->json([
                "success" => FALSE,
                "message" => "Line ID or Member code duplicate.",
            ]);
        } else {
            return response()->json([
                "success" => FALSE,
                "message" => "member update fail.",
            ]);
        }
    }
    public function del($id)
    {
        try {
            Member::where('member_id', $id)->update(['active' => 0]);
            return response()->json([
                "success" => true,
                "message" => "member deleted successfully.",
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                "success" => FALSE,
                "message" => "member deleted fail.",
            ]);
        }
    }
    public function historyBuy(Request $request, $id)
    {
        $total = 0;
        $StartDateTime = $request->input('StartDateTime');
        $EndDateTime = $request->input('EndDateTime');
        $EndDateTime .= " 23:59";
        $data = DB::table('orders')->select('*')->where('member_id', $id)->where("active", 1)->where('created_datetime',  '>=', $StartDateTime)->where('created_datetime', '<=', $EndDateTime)->orderBy('created_datetime', 'desc')->get();
        $dataSum = DB::table('orders')->select(DB::raw("SUM(order_lines.subtotal) as sumTotal"))
            ->join('order_lines', 'orders.order_id', '=', 'order_lines.order_id')

            ->where('order_lines.product_id', '!=', 1)
            ->where('orders.member_id', $id)
            ->where("orders.active", 1)
            ->where('orders.created_datetime',  '>=', $StartDateTime)
            ->where('orders.created_datetime', '<=', $EndDateTime)->orderBy('orders.created_datetime', 'desc')->first();

        return response()->json([
            "success" => true,
            "message" => "Member List",
            "data" => $data,
            "sum" => $dataSum->sumTotal
        ]);
    }
    public function DebtReport()
    {
        $total = 0;

        $data = Member::select('*')->where('active', 1)->where('debt', '>', 0)->get();
        $jsonarray = json_decode(json_encode($data), TRUE);
        foreach ($jsonarray as $datamember) {
            $total += $datamember["debt"];
        }
        return response()->json([
            "success" => true,
            "message" => "member Debt List",
            "data" => $data,
            "sum" => $total

        ]);
    }
    public function search($name)
    {
        $data = Member::select('*')->where("active", 1)
            ->where('Firstname', 'LIKE', "%$name%")
            ->orWhere('Lastname', 'LIKE', "%$name%")
            ->orWhere('Nickname', 'LIKE', "%$name%")
            ->orWhere('member_code', 'LIKE', "%$name%")
            ->orWhere('phone_number', 'LIKE', "%$name%")
            ->get();

        return response()->json([
            "success" => true,
            "message" => "Member List",
            "data" => $data
        ]);
    }

    public function searchNameLastname($name)
    {
        $data = Member::select('*')->where("active", 1)
            ->where('Firstname', 'LIKE', "%$name%")
            ->orWhere('Lastname', 'LIKE', "%$name%")
            ->orWhere('Nickname', 'LIKE', "%$name%")
            ->orWhere('member_code', 'LIKE', "%$name%")
            ->orWhere('phone_number', 'LIKE', "%$name%")
            ->get();

        return response()->json([
            "success" => true,
            "message" => "Member List",
            "data" => $data
        ]);
    }
}
