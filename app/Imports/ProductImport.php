<?php

namespace App\Imports;

use App\Models\Product;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithUpserts;
use Maatwebsite\Excel\Concerns\WithUpsertColumns;
class ProductImport implements ToModel,WithHeadingRow, WithUpserts, WithUpsertColumns
{
    /**
    * @param array $row
    *
    * @return \Illuminate\Database\Eloquent\Model|null
    */
    public function model(array $row)
    {
        // $type=1;
        // $image="public/files/defaultproduct.png"
        return new Product([
            'product_id'=>$row['product_id'],
            'product_name'=>$row['product_name'],
            'price'=>$row['price'],
            'image'=>"public/files/defaultproduct.png",
        ]);
    }
    public function uniqueBy() {
        return 'product_id';
    }
    public function upsertColumns()
    {
        return ['product_id'];
    }

}
