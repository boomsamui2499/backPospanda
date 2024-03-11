<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Pos_pin;
use App\Models\Product_uom;
use App\Models\ProductPricelist;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use thiagoalessio\TesseractOCR\TesseractOCR;
use App\Http\Resources\ProductResource;
use App\Http\Resources\ProductPricelistResource;
use App\Http\Resources\PospinResource;
use App\Models\Category;
use App\Models\Expiration_log;
use App\Models\MetaData;
use App\Models\Out_of_stock;
use App\Models\Pricelist;
use Carbon\Carbon;
use Illuminate\Support\Str;


class ProductController extends Controller
{

  private function clear()
  {
    DB::statement("SET foreign_key_checks=0");
    Category::query()->truncate();
    Pricelist::query()->truncate();
    Product_uom::query()->truncate();
    ProductPricelist::query()->truncate();
    Product::query()->truncate();
    // Category::truncate();
    // Product::truncate();
    // Product_uom::truncate();
    // ProductPricelist::truncate();
    // Pricelist::truncate();
    DB::statement("SET foreign_key_checks=1");
  }
  public function index()
  {
    $products = ProductResource::collection(
      Product::where("active", 1)->where('product_id', '!=', 1)->where('product_id', '!=', 2)->get()
    );
    return response()->json([
      "success" => true,
      "message" => "Product List",
      "data" => $products
    ]);
  }
  public function searchPricelist_idAndCategory_id($pospin_id, $category_id, $pricelist_id)
  {
    $datanew = array();
    if ($pospin_id == 1 && $category_id == 0) {
      $data = Pos_pin::orderBy('sequence', 'asc')->where("active", 1)->get();
      $products = PospinResource::collection($data);
      $jsonarray = json_decode(json_encode($products), TRUE);
      for ($i = 0; $i < $products->count(); $i++) {
        $obj = $jsonarray[$i]["product"];
        $obj["image_url"] = $jsonarray[$i]["image_url"];
        $obj["pos_pin_id"] = $jsonarray[$i]["pos_pin_id"];
        $obj["sequence"] = $jsonarray[$i]["sequence"];
        // $obj["pricelist_by_id"] = $jsonarray[$i]["pricelist_by_id"];
        if (!$obj["product_pricelist"] == []) {
          $countJ = $jsonarray[$i]["product"]["product_pricelist"];
          for ($j = 0; $j < count($countJ); $j++) {
            if ($obj["product_pricelist"][$j]["pricelist_id"] == $pricelist_id) {
              $obj["price"] = $obj["product_pricelist"][$j]["price"];
            }
          }
        } else {
          $obj["price"] = $jsonarray[$i]["product"]["price"];
        }
        array_push($datanew, $obj);
      }
    } elseif ($category_id == 0) {
      $products = ProductPricelistResource::collection(Product::where("active", 1)->get());
      $jsonarray = json_decode(json_encode($products), TRUE);
      for ($i = 0; $i < $products->count(); $i++) {
        $obj = $jsonarray[$i];
        if ($jsonarray[$i]["pricelist_by_id"] == []) {
          $obj["price"] = $jsonarray[$i]["price"];
        } elseif ($jsonarray[$i]["pricelist_by_id"][0]["price"]) {
          $obj["price"] = $jsonarray[$i]["pricelist_by_id"][0]["price"];
        } else {
          $obj["price"] = $jsonarray[$i]["price"];
        }
        array_push($datanew, $obj);
      }
    } else {
      $products = ProductPricelistResource::collection(Product::where('product_id', '!=', 1)->where('product_id', '!=', 2)->where("active", 1)->where("category_id", $category_id)->get());
      $jsonarray = json_decode(json_encode($products), TRUE);
      for ($i = 0; $i < $products->count(); $i++) {
        $obj = $jsonarray[$i];
        if ($jsonarray[$i]["pricelist_by_id"] == []) {
          $obj["price"] = $jsonarray[$i]["price"];
        } elseif ($jsonarray[$i]["pricelist_by_id"][0]["price"]) {
          $obj["price"] = $jsonarray[$i]["pricelist_by_id"][0]["price"];
        } else {
          $obj["price"] = $jsonarray[$i]["price"];
        }
        array_push($datanew, $obj);
      }
    }
    return response()->json([
      "success" => true,
      "message" => "Product List",
      "data" => $datanew
    ]);
  }



  public function showFitterCatagory($id)
  {

    $data = ProductResource::collection(Product::where('product_id', '!=', 1)->where('product_id', '!=', 2)->where("active", 1)->where('category_id', $id)->get());

    if ($id == 0) {
      $data = ProductResource::collection(Product::where('product_id', '!=', 1)->where('product_id', '!=', 2)->where("active", 1)->get());
    }

    return response()->json([
      "success" => true,
      "message" => "Product List",
      "data" => $data
    ]);
  }
  public function search($name)
  {
    // $data = DB::table('product')->select('*')->where('product_name', 'LIKE', "%$name%")->get();
    $data = ProductResource::collection(Product::where("active", 1)->where('product_name', 'LIKE', "%$name%")
      ->orWhere('barcode', 'LIKE', "%$name%")
      ->orWhere('product_id', 'LIKE', "%$name%")->where("active", 1)
      ->get());

    // $product = Product::all();
    return response()->json([
      "success" => true,
      "message" => "Product List",
      "data" => $data
    ]);
  }
  public function searchBarcode($barcode, $price_list)
  {
    $newData = array();
    $dataPerfix = MetaData::select('meta_value')->where("active", 1)->where("meta_module", 'store')->where("meta_key", 'store_scale_prefix')->first();
    $dataPerfixInt = (int)$dataPerfix["meta_value"];
    $resultProduct = Product::where("active", 1)->Where('barcode', $barcode)->get();
    $resultProductUom = Product_uom::where("active", 1)->Where('barcode', $barcode)->get();
    $prefix = Str::substr($barcode, 0, 2);
    if ($dataPerfixInt == $prefix) {
      $Bcode = Str::substr($barcode, 2, 5);
      $weight = Str::substr($barcode, 7, 5);
      $resultProductScale = Product::where("active", 1)->Where('barcode', $Bcode)->with("product_uom")->first();
      if (!$resultProductScale == []) {
        $data = Product::where("active", 1)->with("product_uom")
          ->Where('product_id', $resultProductScale->product_id)
          ->get();
        $dataSet = ProductResource::collection($data);
        $qty = $weight / 1000;
        foreach ($dataSet as $dataSets) {
          $dataSets["current_average_cost"] = $qty;
        }

        if ($price_list > 0) {
          $resultProductPriceList = ProductPricelist::select("price")->Where('product_id', $resultProductScale->product_id)
            ->Where('pricelist_id', $price_list)->first();
          $dataSets->price =  $resultProductPriceList->price;
        }

        array_push($newData, $dataSets);
        return response()->json([
          "success" => true,
          "is_scale" => true,
          "is_UOM" => false,
          "message" => "Product Barcode Scale ",
          "weight" => $qty,
          "data" => $dataSets
        ]);
      } else {
        return response()->json([
          "success" => false,
          "is_scale" => false,
          "message" => "Barcode Scale Not Found"
        ]);
      }
    }
    if (!$resultProduct->isEmpty()) {

      $data = Product::where("active", 1)->with("product_uom")
        ->Where('product_id', $resultProduct[0]->product_id)
        ->get();
      $dataSet = ProductResource::collection($data);
      if ($price_list > 0) {
        $resultProductPriceList = ProductPricelist::select("price")->Where('product_id', $resultProduct[0]->product_id)
          ->Where('pricelist_id', $price_list)->first();
        $dataSet[0]->price =  $resultProductPriceList->price;
      }

      return response()->json([
        "success" => true,
        "is_UOM" => false,
        "message" => "Product Bar Code ",
        "data" => $dataSet
      ]);
    } elseif (!$resultProductUom->isEmpty()) {
      $data = Product::where("active", 1)->with("product_uom")
        ->Where('product_id', $resultProductUom[0]->product_id)
        ->get();
      $dataSet = ProductResource::collection($data);

      foreach ($resultProductUom as $results) {
        $mapdata["product_uom_id"] = $results->product_uom_id;
        $mapdata["product_uom_name"] = $results->product_uom_name;
        $mapdata["price"] = $results->price;
      }
      return response()->json([
        "success" => true,
        "is_UOM" => true,
        "message" => "UOM Bar Code ",
        "dataUOM" => $mapdata,
        "data" => $dataSet
      ]);
    } {
      return response()->json([
        "success" => false,
        "message" => "Barcode Not Found"
      ]);
    }
  }

  public function add(Request $request)
  {

    $array = array();
    $data = null;
    $product_name = $request->input('product_name');
    $price = $request->input('price');
    $type = $request->input('type');
    $barcode = $request->input('barcode');
    $category_id = $request->input('category_id');
    // $stock_qty = $request->input('stock_qty');
    $image = $request->input('image');
    $is_vat = $request->input('is_vat');
    $is_scale = $request->input('is_scale');
    // $current_average_cost = $request->input('current_average_cost');
    DB::beginTransaction();
    $dataproduct = ($request->input());

    if ($product_name || $barcode) {
      $checkDuplicate = DB::table('product')->select('*')->where('product_name', $product_name)->orWhere('barcode', $barcode)->where('barcode', '!=', NULL)->where('product_id', '!=', 1)->where('product_id', '!=', 2)->get();

      if ($checkDuplicate->count() >= 1) {
        DB::rollBack();
        return response()->json([
          "success" =>  false,
          "message" => "สินค้านี้มีชื่อนี้อยู่ในระบบแล้ว",
        ]);
      }
    }
    $id =   Product::insertGetId([
      'product_name' => $product_name,
      'price' => $price,
      'type' => $type,
      'barcode' => $barcode,
      'category_id' => $category_id,
      'image' => $image,
      'is_vat' => $is_vat,
      'is_scale' => $is_scale
    ]);

    if (!$request->input("uom") == []) {
      $jsonarray = json_decode(json_encode($request->input("uom")), TRUE);

      foreach ($jsonarray as $data) {
        $checkDuplicate =
          DB::table('product')->select("*")->Where('product.barcode', $data["barcode"])->where('product.barcode', '!=', NULL)->where('product.product_id', '!=', 1)->where('product.product_id', '!=', 2)->get();
        $checkDuplicateUOM = DB::table('product_uom')->select("*")->Where('barcode', $data["barcode"])->where('barcode', '!=', NULL)->get();

        if ($checkDuplicate->count() >= 1) {
          DB::rollBack();
          return response()->json([
            "success" =>  false,
            "message" => "บาร์โค้ดซ้ำกับสินค้า",
          ]);
        }
        if ($checkDuplicateUOM->count() >= 1) {
          DB::rollBack();
          return response()->json([
            "success" =>  false,
            "message" => "บาร์โค้ดซ้ำกับUOM",
          ]);
        }
        // }
        Product_uom::insert([
          'product_uom_name' => $data["product_uom_name"],
          'multiple_qty' => $data["multiple_qty"],
          'product_id' => $id,
          'barcode' => $data["barcode"],
          'price' => $data["price"]
        ]);
      }
    }
    if (!$dataproduct["pricelist"] == []) {
      $dataproduct = ($request->input());
      $jsonarray = json_decode(json_encode($request->input("pricelist")), TRUE);

      foreach ($jsonarray as $data) {
        if (!$data["price"] == null) {
          ProductPricelist::insert([
            'pricelist_id' => $data["pricelist_id"],
            'product_id' => $id,
            'price' => $data["price"]
          ]);
        }
      }
      DB::commit();
      return response()->json([
        "success" => true,
        "message" => "Product created successfully.",
      ]);
    }
    if ($id) {
      DB::commit();
      return response()->json([
        "success" => true,
        "message" => "Product created successfully Not have UOM anm Pricelist",
      ]);
    } else {
      DB::rollBack();
      return response()->json([
        "success" => FALSE,
        "message" => "Product created fail.",
      ]);
    }
  }
  public function addNew(Request $request)
  {

    $array = array();
    $data = null;
    $product_name = $request->input('product_name');
    $price = $request->input('price');
    $type = $request->input('type');
    $barcode = $request->input('barcode');
    $category_id = $request->input('category_id');
    // $stock_qty = $request->input('stock_qty');
    $image = $request->input('image');
    $is_vat = $request->input('is_vat');
    $is_scale = $request->input('is_scale');
    // $current_average_cost = $request->input('current_average_cost');
    DB::beginTransaction();
    $dataproduct = ($request->input());

    if ($product_name || $barcode) {
      $checkDuplicate = DB::table('product')->select('*')->where('product_name', $product_name)->orWhere('barcode', $barcode)->where('barcode', '!=', NULL)->where('product_id', '!=', 1)->where('product_id', '!=', 2)->get();

      if ($checkDuplicate->count() >= 1) {
        DB::rollBack();
        return response()->json([
          "success" =>  false,
          "message" => "สินค้านี้มีชื่อนี้อยู่ในระบบแล้ว",
        ]);
      }
    }
    $id =   Product::insertGetId([
      'product_name' => $product_name,
      'price' => $price,
      'type' => $type,
      'barcode' => $barcode,
      'category_id' => $category_id,
      'image' => $image,
      'is_vat' => $is_vat,
      'is_scale' => $is_scale
    ]);







    if ($id) {
      DB::commit();
      return response()->json([
        "success" => true,
        "message" => "Product created successfully Not have UOM anm Pricelist",
      ]);
    } else {
      DB::rollBack();
      return response()->json([
        "success" => FALSE,
        "message" => "Product created fail.",
      ]);
    }
  }



  public function show($id)
  {
    $products = ProductResource::collection(Product::where("active", 1)->where("product_id", $id)->get());

    if (!$products->isEmpty()) {

      return response()->json([
        "success" => true,
        "message" => "Product found.",
        "data" => $products
      ]);
    } else {
      return response()->json([
        "success" => FALSE,
        "message" => "Product not found.",
      ]);
    }
  }

  public function update(Request $request, $id)
  {
    DB::beginTransaction();
    try {
      $array = array();
      $dataproduct = ($request->input());
      $dataproduct = Arr::except($dataproduct, ['uom']);
      $dataproduct = Arr::except($dataproduct, ['pricelist']);
      $checkDuplicateProduct = DB::table('product')->select('*')->where('product_name', $dataproduct["product_name"])->where('product_id', '!=', $id)->get();
      $checkDuplicateProductBarcode = DB::table('product')->select('*')->where('barcode', $dataproduct["barcode"])->where('barcode', '!=', null)->where('product_id', '!=', $id)->get();

      if ($checkDuplicateProduct->count() >= 1) {
        DB::rollBack();
        return response()->json([
          "success" =>  false,
          "message" => "สินค้ามีชื่อนี้อยู่ในระบบแล้ว",
        ]);
      }
      if ($checkDuplicateProductBarcode->count() >= 1) {
        DB::rollBack();
        return response()->json([
          "success" =>  false,
          "message" => "สินค้ามีบาร์โค้ดนี้อยู่ในระบบแล้ว",
        ]);
      }

      $data = Product::where('product_id', $id)->update($dataproduct);
      $dataUom = ($request->input("uom"));
      $dataPricelist = ($request->input("pricelist"));
      $dataUpsertPriceList = [];
      if (!$dataPricelist == []) {
        foreach ($dataPricelist as $key => $value) {
          if (isset($value["price"])) {

            $obj["product_id"] = $id;
            $obj["pricelist_id"] = $value["pricelist_id"];
            $obj["price"] = $value["price"];
            $obj["product_pricelist_id"] = $value["product_pricelist_id"];
            array_push($dataUpsertPriceList, $obj);
          }
        }
        DB::table('product_pricelist')->upsert($dataUpsertPriceList, ["pricelist_id", "product_id", "product_pricelist_id"]);
      }
      if (!$dataUom == []) {
        foreach ($dataUom as $key => $value) {
          if (isset($value['product_uom_id']) == false) {
            $value['product_uom_id'] = null;
          }
          $checkDuplicate = DB::table('product')->select("*")->Where('product.barcode', $value["barcode"])->where('product.barcode', '!=', NULL)->where('product.product_id', '!=', 1)->where('product.product_id', '!=', 2)->get();
          $checkDuplicateUOM = DB::table('product_uom')->select("*")->Where('barcode', $value["barcode"])->where('barcode', '!=', NULL)->where('product_uom_id', '!=', $value["product_uom_id"])->get();
          $checkDuplicateNameUOM = DB::table('product_uom')->select("*")->Where('product_uom_name', $value["product_uom_name"])->where('product_id', $id)->where('product_uom_id', '!=', $value["product_uom_id"])->where('active', 1)->get();
          if ($checkDuplicateNameUOM->count() > 0) {
            DB::rollBack();
            return response()->json([
              "success" =>  false,
              "message" => "มีชื่อUOMนี้กับสินค้าในระบบแล้ว",
            ]);
          }
          if ($checkDuplicate->count() >= 1) {
            DB::rollBack();
            return response()->json([
              "success" =>  false,
              "message" => "บาร์โค้ดซ้ำกับสินค้า",
            ]);
          }
          if ($checkDuplicateUOM->count() >= 1) {
            DB::rollBack();
            return response()->json([
              "success" =>  false,
              "message" => "บาร์โค้ดซ้ำกับUOM",
            ]);
          }
          $value["product_id"] = $id;
          DB::table('product_uom')->upsert($value, ["product_uom_id", "product_id"]);
        }
      }
      DB::commit();
      return response()->json([
        "success" => true,
        "message" => "Product update successfully.",
      ]);
    } catch (\Throwable $e) {
      DB::rollBack();
      return response()->json([
        "success" => FALSE,
        "message" => "Product update fail.",
      ]);
    }
  }

  public function del($id)
  {
    try {
      $datetimecurrent = Carbon::now()->timestamp;
      $dataPin = DB::table('pos_pin')->select('product_id')->where('product_id', $id)->where('active', 1)->first();
      $dataExp_log = Expiration_log::select('product_id')->where('product_id', $id)->where('active', 1)->first();
      $dataOut_of_stock = Out_of_stock::select('product_id')->where('product_id', $id)->where('active', 1)->first();
      $data_product_uom = Product_uom::select('product_id')->where('product_id', $id)->where('active', 1)->first();
      $dataProduct = DB::table('product')->select('product_name')->where('product_id', $id)->where('active', 1)->first();
      $product_name = $dataProduct->product_name;
      $product_name .= " ";
      $product_name .= $datetimecurrent;
      Product::where('product_id', $id)->update(['product_name' => $product_name, 'barcode' => NULL, 'active' => 0]);
      Product_uom::where('product_id', $id)->update(['product_uom_name' => $product_name, 'barcode' => NULL, 'active' => 0]);
      Product::where('product_id', $id)->update(['active' => 0]);
      if (!$dataPin == []) {
        Pos_pin::where('product_id', $id)->update(['active' => 0]);
      }
      if (!$dataExp_log == []) {
        Expiration_log::where('product_id', $id)->update(['active' => 0]);
      }
      if (!$dataOut_of_stock == []) {
        Out_of_stock::where('product_id', $id)->update(['active' => 0]);
      }
      if (!$data_product_uom == []) {
        Product_uom::where('product_id', $id)->update(['active' => 0]);
      }
      return response()->json([
        "success" => true,
        "message" => "Product deleted successfully.",
      ]);
    } catch (\Throwable $e) {
      return response()->json([
        "success" => FALSE,
        "message" => "Product deleted fail.",
      ]);
    }
  }
  public function stock()
  {
    $stock = Product::select('product_id', 'product_name', 'stock_qty', 'current_average_cost')->where("active", 1)->where("type", 1)->get();
    $datanew = array();
    $i = 1;
    $total = 0;
    foreach ($stock as $data) {
      $obj["index"] = $i++;
      $obj["product_id"] = $data->product_id;
      $obj["product_name"] = $data->product_name;
      $obj["stock_qty"] = $data->stock_qty;
      $obj["current_average_cost"] = $data->current_average_cost;
      $obj["product_result"] = floor(($data->current_average_cost * $data->stock_qty) * 100) / 100;
      $total += ($data->current_average_cost * $data->stock_qty);
      array_push($datanew, $obj);
    }

    return response()->json([
      "success" => true,
      "message" => "Product List",
      "data" => $datanew,
      "total" => $total

    ]);
  }

  public function searchStock($name)
  {
    $stock = Product::select('product_id', 'product_name', 'stock_qty', 'current_average_cost')
      ->where("active", 1)->where("type", 1)
      ->where('product_name', 'LIKE', "%$name%")
      ->orWhere('barcode', 'LIKE', "%$name%")->get();
    $datanew = array();
    $i = 1;
    foreach ($stock as $data) {
      $obj["index"] = $i++;
      $obj["product_id"] = $data->product_id;
      $obj["product_name"] = $data->product_name;
      $obj["stock_qty"] = $data->stock_qty;
      $obj["current_average_cost"] = $data->current_average_cost;
      $obj["product_result"] = floor(($data->current_average_cost * $data->stock_qty) * 100) / 100;
      array_push($datanew, $obj);
    }
    return response()->json([
      "success" => true,
      "message" => "Product List",
      "data" => $datanew
    ]);
  }
  public function syncProduct(Request $request)
  {
    $this->clear();
    $array = array();
    $data = null;
    DB::beginTransaction();
    try {
      $datatoken = MetaData::select('meta_value')->where("active", 1)->where("meta_module", 'branch')->where("meta_key", 'branch_token')->first();
      $token_for_db = (int)$datatoken->meta_value;
      $token_for_required = (int)$request->input('token');
      // error_log($request->input('meta_key'));

      if ($token_for_db == $token_for_required) {

        $jsonarray = json_decode(json_encode($request->input('product')), TRUE);
        $jsonarrayCategory = json_decode(json_encode($request->input('category')), TRUE);
        $jsonarrayPricelist = json_decode(json_encode($request->input('pricelist')), TRUE);
        foreach ($jsonarrayCategory as  $valueCategory) {
          Category::insert([
            'category_id' => $valueCategory["category_id"],
            'category_name' => $valueCategory["category_name"],
            "sequence" => $valueCategory["sequence"]
          ]);
        }
        foreach ($jsonarrayPricelist as  $valuePricelist) {
          Pricelist::insert([
            'pricelist_id' => $valuePricelist["pricelist_id"],
            'pricelist_name' => $valuePricelist["pricelist_name"]
          ]);
        }
        foreach ($jsonarray as  $value) {
          $product_name = $value["product_name"];
          $price = $value["price"];
          $type = $value["type"];
          $barcode = $value["barcode"];
          $category_id = $value["category_id"];
          $image = $value["image"];
          $is_vat = $value["is_vat"];
          $is_scale = $value["is_scale"];

          Product::insert([
            'product_name' => $product_name,
            'price' => $price,
            'type' => $type,
            'barcode' => $barcode,
            'category_id' => $category_id,
            'image' => $image,
            'is_vat' => $is_vat,
            'is_scale' => $is_scale
          ]);
          if (!$value["product_uom"] == []) {
            $ProductUomjsonarray = json_decode(json_encode($value["product_uom"]), TRUE);
            foreach ($ProductUomjsonarray as $data) {

              Product_uom::insert([
                'product_uom_name' => $data["product_uom_name"],
                'multiple_qty' => $data["multiple_qty"],
                'product_id' => $data["product_id"],
                'barcode' => $data["barcode"],
                'price' => $data["price"]
              ]);
            }
          }
          if (!$value["pricelist"] == []) {
            $ProductPriceListjsonarray = json_decode(json_encode($value["pricelist"]), TRUE);

            foreach ($ProductPriceListjsonarray as $data) {
              // if (!$data["price"] == null) {
              ProductPricelist::insert([
                'pricelist_id' => $data["pricelist_id"],
                'product_id' => $data["product_id"],
                'price' => (int)$data["price"]
              ]);
              // }
            }
          }
        }

        DB::commit();
        return response()->json([
          "success" => true,
          "message" => "Product sync successfully",
        ]);
      } else {
        DB::rollBack();

        return response()->json([
          "status" => false,
          "message" => "Token ไม่เหมือนกัน "
        ], 401);
      }
    } catch (\Throwable $e) {
      DB::rollBack();
      error_log($e);
      return response()->json([
        "success" => $ProductPriceListjsonarray,
        "message" => "Product sync fail.",
      ], 400);
    }
  }
}
