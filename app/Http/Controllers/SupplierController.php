<?php

namespace App\Http\Controllers;


use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use App\Models\Supplier;

class SupplierController extends Controller
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
        $data = Supplier::select('*')->where('active', 1)->get();
        return response()->json([
            "success" => true,
            "message" => "supplier List",
            "data" => $data
        ]);
    }
    public function showbyid($id)
    {
        $data = Supplier::select('*')->where('active', 1)->where('supplier_id', $id)->get();
        if ($id == 0) {
            $data = Supplier::select('*')->where('active', 1)->get();
        }
        return response()->json([
            "success" => true,
            "message" => "supplier List",
            "data" => $data
        ]);
    }
    public function add(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'Firstname' => 'required|string|between:2,100',
            'Lastname' => 'required|string|between:2,100',
            'phone_number' => 'required|string|between:2,100',

        ]);
        if ($validator->fails()) {
            return response()->json([
                "status" => false,
                "messageError" => $validator->errors()->toJson()
            ], 400);
        }
        $registered_date = $this->datetimecurrent();
        if ($request->input()) {
            Supplier::insert([
                'Firstname' => $request->input('Firstname'),
                'Lastname' => $request->input('Lastname'),
                'Nickname' => $request->input('Nickname'),
                'gender' => $request->input('gender'),
                'phone_number' => $request->input('phone_number'),
                "address_line1" => $request->input('address_line1'),
                "address_line2" => $request->input('address_line2'),
                "province" => $request->input('province'),
                "zip_code" => $request->input('zip_code'),
                "tax_registered_number" => $request->input('tax_registered_number'),
                "company_name" => $request->input('company_name'),
                "registered_date" => $registered_date
            ]);
            return response()->json([
                "success" => true,
                "message" => "supllier created successfully.",
            ]);
        } else {
            return response()->json([
                "success" => false,
                "message" => "supllier created fail.",
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
        $data = Supplier::select('*')->where('tax_registered_number', $request->input("tax_registered_number"))->where('supplier_id', '!=', $id)->get();

        if ($data->count() == 0) {
            Supplier::where('supplier_id', $id)->update($request->input());
            return response()->json([
                "success" => true,
                "message" => "supplier update successfully.",
            ]);
        } elseif ($data->count() >= 1) {
            return response()->json([
                "success" => FALSE,
                "message" => "Tax number duplicate.",
            ]);
        } else {
            return response()->json([
                "success" => FALSE,
                "message" => "supplier update fail.",
            ]);
        }
    }
    public function del($id)
    {
        try {
            Supplier::where('supplier_id', $id)->update(['active' => 0]);
            return response()->json([
                "success" => true,
                "message" => "supplier deleted successfully.",
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                "success" => FALSE,
                "message" => "supplier deleted fail.",
            ]);
        }
    }
}
