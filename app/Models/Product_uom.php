<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product_uom extends Model
{
    use HasFactory;
    protected $table = 'product_uom';
    protected $primaryKey = 'product_uom_id';

    protected $fillable = [
        'product_uom_id',
        'product_uom_name',
        'product_id',
        'multiple_qty',
        'price',
        'barcode'
    ];
    protected $casts = [
        'product_uom_id' => 'integer',
        'price' => 'float',
        'multiple_qty' => 'float',
    ];
}
