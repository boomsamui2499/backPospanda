<?php

namespace App\Imports;

use App\Models\stock_adjustment;
use Maatwebsite\Excel\Concerns\ToModel;

class ImportStock implements ToModel
{
    /**
     * @param array $row
     *
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function model(array $row)
    {
        return new stock_adjustment([
            'stock_adjustment_id' => $row['stock_adjustment_id'],
            'barcode' => $row['barcode'],
            'stock_adjustment_name' => $row['stock_adjustment_name'],
            'type' => 1,
        ]);
    }

    public function uniqueBy()
    {
        return 'stock_adjustment_id';
    }
    public function upsertColumns()
    {
        return ['stock_adjustment_id'];
    }
}
