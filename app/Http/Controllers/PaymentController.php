<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\Payment;

class PaymentController extends Controller
{
    public function index()
    {
        $data = Payment::select('*')->where('active', 1)->get();
        return response()->json([
            "success" => true,
            "message" => "payment List",
            "data" => $data
        ]);
    }


    public function showFitterpayment($id)
    {

        $data = Payment::select('*')->where('active', 1)->where('payment_id', $id)->get();
        if ($id == 0) {
            $data = Payment::select('*')->where('active', 1)->get();
        }
        return response()->json([
            "success" => true,
            "message" => "payment List",
            "data" => $data
        ]);
    }

    public function add(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'payment_name' => 'required|string|between:2,100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                "status" => 500,
                "message" => $validator->errors()
            ]);
        }
        $paymentName = Payment::select('*')->where('payment_name', $request->input("payment_name"))->get();
        // var_dump($paymentName[0]);
        // die;
        if ($paymentName->count() == 0) {
            Payment::insert([
                'payment_name' => $request->input('payment_name')
            ]);
            return response()->json([
                "success" => true,
                "message" => "payment created new successfully.",
            ]);
        }
        if ($paymentName->count() >= 1 && $paymentName[0]->active == 0) {
            Payment::where('payment_id', $paymentName[0]->payment_id)->where('is_special_payment', 1)->update(['active' => 1]);
            return response()->json([
                "success" => true,
                "message" => "payment created successfully.",
            ]);
        } elseif ($paymentName->count() >= 1 && $paymentName[0]->active == 1) {
            return response()->json([
                "success" => FALSE,
                "message" => "Payment Name duplicate.",
            ]);
        } else {
            return response()->json([
                "success" => FALSE,
                "message" => "payment fail successfully.",
            ]);
        }
    }

    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'payment_name' => 'required|string|between:2,100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                "status" => 500,
                "message" => $validator->errors()
            ]);
        }

        $paymentName = Payment::select('*')->where('payment_name', $request->input("payment_name"))->where('payment_id', '!=', $id)->get();
        // var_dump($paymentName);
        // die;
        if ($paymentName->count() == 0) {
            Payment::where('payment_id', $id)->where('is_special_payment', 1)->update(['payment_name' => $request->input("payment_name")]);
            return response()->json([
                "success" => true,
                "message" => "payment update new successfully.",
            ]);
        }

        if ($paymentName->count() == 1 && $paymentName[0]->active == 0) {
            Payment::where('is_special_payment', 1)->where('payment_name', $paymentName[0]->payment_name)->update(['active' => 1]);
            Payment::where('is_special_payment', 1)->where('payment_id', $id)->update(['active' => 0]);
            return response()->json([
                "success" => true,
                "message" => "payment update successfully.",
            ]);
        } elseif ($paymentName->count() >= 1 && $paymentName[0]->active == 1) {
            return response()->json([
                "success" => FALSE,
                "message" => "Payment Name duplicate.",
            ]);
        } else {
            return response()->json([
                "success" => FALSE,
                "message" => "payment fail successfully.",
            ]);
        }
    }

    public function del(Request $request, $id)
    {

        $data = Payment::where('payment_id', $id)->where('is_special_payment', 1)->update(['active' => 0]);


        if ($data == 1) {

            return response()->json([
                "success" => true,
                "message" => "payment delete successfully.",
            ]);
        } else {
            return response()->json([
                "success" => false,
                "message" => "payment delete fail.",
            ]);
        }
    }
}
