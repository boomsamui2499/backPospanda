<?php

namespace App\Http\Controllers;

use App\Models\Note_coffee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
class NoteCoffeeController extends Controller
{
    public function index()
    {
        $data = Note_coffee::select('note_coffee_id','note')->get();
        return response()->json([
            "success" => true,
            "message" => "Note List",
            "data" => $data
        ]);
    }
    public function add(Request $request)
    {
        $validator = Validator::make($request->all(), ['note' => 'required|string|between:2,100',]);

        if ($validator->fails()) {
            return response()->json([
                "status" => false,
                "messageError" => $validator->errors()->toJson()
            ], 400);
        }
            Note_coffee::insert(['note' => $request->input('note')]);
            return response()->json([
                "success" => true,
                "message" => "Note created successfully.",
            ]);
       
    }
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), ['note' => 'required|string|between:2,100',]);

        if ($validator->fails()) {
            return response()->json([
                "status" => false,
                "messageError" => $validator->errors()->toJson()
            ], 400);
        }

        Note_coffee::where('note_coffee_id', $id)->update($request->input());
            return response()->json([
                "success" => true,
                "message" => "Note update successfully.",
            ]);
       
    }
    public function del($id)
    {
        try {
            Note_coffee::where('note_coffee_id', $id)->delete();
            return response()->json([
                "success" => true,
                "message" => "Note deleted successfully.",
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                "success" => FALSE,
                "message" => "Note deleted fail.",
            ]);
        }
    }}
