<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\User;

class UserController extends Controller
{
    public function index()
    {
        $data = DB::table('users')->select('*')->where('active', 1)->get();

        // $category = Product::all();
        return response()->json([
            "success" => true,
            "message" => "User List",
            "data" => $data
        ]);
    }


    public function add(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|between:2,100',
            'last_name' => 'required|string|between:2,100',
            'phone_number' => 'required|string|between:2,100',
            'email' => 'required|string|email|max:100|unique:users',
            'username' => 'required|string',
            'password' => 'required|string|confirmed|min:6',
            'permission' => 'required|string',
        ]);
        $emailDuplicate = User::select('*')->where("active", 1)->where("email", $request->input("email"))->get();
        if ($emailDuplicate->count() >= 1) {
            return response()->json([
                "success" =>  false,
                "message" => "Email นี้มีชื่อนี้อยู่ในระบบแล้ว",
            ]);
        }
        if ($validator->fails()) {
            return response()->json([
                "status" => false,
                "messageError" => $validator->errors()
            ], 400);
        }
        $user = User::create(array_merge(
            $validator->validated(),
            ['password' => bcrypt($request->password)]
        ));
        return response()->json([
            'success' => true,
            'message' => 'User successfully registered',
            'user' => $user
        ], 201);
    }

    public function show($id)
    {
        $data = DB::table('users')->select('*')->where('id', $id)->where('active', 1)->get();

        if (!$data->isEmpty()) {

            return response()->json([
                "success" => true,
                "message" => "user found.",
                "data" => $data
            ]);
        } else {
            return response()->json([
                "success" => false,
                "message" => "user not found."
            ]);
        }
    }

    public function update(Request $request, $id)
    {
        if ($request->input()) {
            DB::table('users')->where('id', $id)->update($request->input());
            return response()->json([
                "success" => true,
                "message" => "update user successfully.",
            ]);
        } else {
            return response()->json([
                "success" => false,
                "message" => "update user fail",
            ]);
        }
    }
    public function updatePassword(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'password' => 'required|string|confirmed|min:6',
        ]);
        if ($validator->fails()) {
            return response()->json([
                "status" => false,
                "messageError" => $validator->errors()->toJson()
            ], 400);
        }
        $newPass = bcrypt($request->input("password"));
        $data = DB::table('users')->where('id', $id)->update(['password' => $newPass]);
        if ($data == 1) {
            // DB::table('users')->where('id', $id)->update(['password' => $newPass]);
            return response()->json([
                "success" => true,
                "message" => "update password successfully.",
            ]);
        } else {
            return response()->json([
                "success" => false,
                "message" => "update password fail.",
            ]);
        }
    }

    public function del($id)
    {
        try {
            // $data = DB::table('users')->select('*')->where('id', $id)->get();

            // DB::table('users')->where('id', $data[0]->id)->update(['active' => 0]);
            User::find($id)->delete();
            return response()->json([
                "success" => true,
                "message" => "user deleted successfully.",
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                "success" => FALSE,
                "message" => "user deleted fail.",
            ]);
        }
    }
}