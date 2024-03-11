<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $product = [
            [
                'product_id' => 1, 'product_name' => "ชำระหนี้", 'price' => 0, 'type' => 0, 'barcode' => 0, 'category_id' => null,
                'image' => 'public/files/E86lCccneBNvQ5a7LjpmBKumAHEpNb4f7YGJQgQx.jpg', 'is_vat' => 0, 'stock_qty' => 0, 'current_average_cost' => 0
            ],
            [
                'product_id' => 2, 'product_name' => "ส่วนลด", 'price' => 0, 'type' => 0, 'barcode' => 0, 'category_id' => null,
                'image' => 'public/files/defaultproduct.png', 'is_vat' => 0, 'stock_qty' => 0, 'current_average_cost' => 0
            ],
        ];
        foreach ($product as $products) {
            DB::table('product')->insert([$products]);
        }
    }
}
