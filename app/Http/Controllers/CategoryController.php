<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

use thiagoalessio\TesseractOCR\TesseractOCR;

class CategoryController extends Controller
{
  /**
   * Display a listing of the resource.
   *
   * @return \Illuminate\Http\Response
   */
  public function index()
  {
    $data = category::select('*')->where('active', 1)->orderBy('sequence', 'asc')->get();

    // $category = Product::all();
    return response()->json([
      "success" => true,
      "message" => "category List",
      "data" => $data
    ]);
  }


  public function add(Request $request)
  {
    $category_name = $request->input('category_name');
    $sequence = $request->input('sequence');
    $data = [];
    if ($request->input()) {
      //   $data = category::select('*')->where('sequence', $sequence)->get();
      // }

      // if ($data->isEmpty()) {  //ถ้า data ว่าง
      category::insert([
        'category_name' => $category_name,
        "sequence" => $sequence
      ]);
      return response()->json([
        "success" => true,
        "message" => "category created successfully.",
      ]);
    } else {
      return response()->json([
        "success" => false,
        "message" => "category created fail.",
      ]);
    }
  }

  public function show($id)
  {
    $data = category::select('*')->where('category_id', $id)->where('active', 1)->get();

    if (!$data->isEmpty()) {

      return response()->json([
        "success" => true,
        "message" => "category found.",
        "data" => $data
      ]);
    } else {
      return $this->sendError('category not found.');
    }
  }

  public function update(Request $request, $id)
  {
    $category_name = $request->input('category_name');
    $sequence = $request->input('sequence');
    $data = [];
    //  var_dump($request->input());
    if ($request->input()) {
      //   $data = category::select('*')->where('sequence', $sequence)->get();
      // }
      // if ($data->isEmpty()) {  //ถ้า data ว่าง
      category::where('category_id', $id)->update($request->input());
      return response()->json([
        "success" => true,
        "message" => "category update successfully.",
      ]);
    } else {
      return response()->json([
        "success" => false,
        "message" => "sequence duplicat.",
      ]);
    }
  }

  public function del($id)
  {
    $dataCatagory = Product::select('category_id')->where('category_id', '=', $id)->where('active', '=', 1)->get();
    if ($dataCatagory->count() == 0) {
      category::where('category_id', $id)->update(['active' => 0]);
      return response()->json([
        "success" => true,
        "message" => "category deleted successfully.",
      ]);
    } else {
      return response()->json([
        "success" => false,
        "message" => "This category is currently active.",
      ]);
    }
  }
  public function search($name)
  {
    // $data = DB::table('product')->select('*')->where('product_name', 'LIKE', "%$name%")->get();
    $data = category::select('*')->where("active", 1)->where('category_name', 'LIKE', "%$name%")
      ->get();

    // $product = Product::all();
    return response()->json([
      "success" => true,
      "message" => "category List",
      "data" => $data
    ]);
  }
}
