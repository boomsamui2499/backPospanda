<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseOrderLine extends Model
{
    use HasFactory;
    protected $table = 'purchase_order_line';
    protected $primaryKey = 'purchase_order_line_id';

    protected $fillable = [
        'product_id',
        'purchase_order_id',
    ];

    protected $casts = [
        'product_id' => 'integer',
        'purchase_order_id' => 'integer',
        'purchase_order_line_id' => 'integer',
    ];
    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id')->with('product_uom');
    }
    public function uom()
    {
        return $this->belongsTo(Product_uom::class, "product_uom_id")->where('active', 1);
    }
}
