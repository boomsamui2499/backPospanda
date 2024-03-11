<?php

namespace App\Http\Controllers;

use App\Models\Pricelist;
// use DateTime;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;

class PricelistController extends Controller
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
        $data = Pricelist::select('*')->where('active', 1)->get();

        if (!$data->isEmpty()) {

            return response()->json([
                "success" => true,
                "message" => "Pricelist found.",
                "data" => $data
            ]);
        } else {
            return response()->json([
                "success" => false,
                "message" => "Pricelist not found.",
            ]);
        }
    }

    public function add(Request $request)
    {
        $pricelist_name = $request->input('pricelist_name');
        $datetime = $this->datetimecurrent();
        $data = [];
        if ($request->input()) {
            Pricelist::insert([
                'pricelist_name' => $pricelist_name,
                "created_datetime" => $datetime
            ]);
            return response()->json([
                "success" => true,
                "message" => "pricelist created successfully.",
            ]);
        } else {
            return response()->json([
                "success" => false,
                "message" => "pricelist created fail.",
            ]);
        }
    }

    public function show($id)
    {
        $data = Pricelist::select('*')->where('pricelist_id', $id)->where('active', 1)->get();
        if (!$data->isEmpty()) {
            return response()->json([
                "success" => true,
                "message" => "Pricelist found.",
                "data" => $data
            ]);
        } else {
            return response()->json([
                "success" => false,
                "message" => "Pricelist not found.",
            ]);
        }
    }

    public function update(Request $request, $id)
    {
        $pricelist_name = $request->input('pricelist_name');
        $datetime = $this->datetimecurrent();
        if ($request->input()) {

            Pricelist::where('active', 1)->where('pricelist_id', $id)->update([
                'pricelist_name' => $pricelist_name
            ]);
            return response()->json([
                "success" => true,
                "message" => "pricelist update successfully.",
            ]);
        } else {
            return response()->json([
                "success" => false,
                "message" => "pricelist update fail.",
            ]);
        }
    }

    public function del($id)
    {
        try {
            $data = Pricelist::select('*')->where('pricelist_id', $id)->get();
            Pricelist::where('pricelist_id', $data[0]->pricelist_id)->update(['active' => 0]);
            return response()->json([
                "success" => true,
                "message" => "pricelist deleted successfully.",
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                "success" => FALSE,
                "message" => "pricelist deleted fail.",
            ]);
        }
    }
}
