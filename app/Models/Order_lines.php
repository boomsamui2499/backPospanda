<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Order;

class Order_lines extends Model
{
    use HasFactory;
    protected $primaryKey = 'order_line_id';
    protected $table = 'order_lines';
    protected $fillable = [
        'price',
        'qty',
        'active'
    ];
    protected $casts = [
        'order_line_id' => 'integer',
        'product_id' => 'integer',
        'order_id' => 'integer',
        'product_uom_id' => 'integer',
        'qty' => 'float',
        'price' => 'float',
        'vat' => 'float',
        'margin' => 'float',
        'total_vat' => 'float',
        'total' => 'float',
        'subtotal' => 'float',
        'total_margin' => 'float',
        'price_change' => 'float',
    ];
    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
    public function uom()
    {
        return $this->belongsTo(Product_uom::class, "product_uom_id")->where('active', 1);
    }
    public function promotion()
    {
        return $this->belongsTo(Promotion::class, "promotion_id");
    }
}
