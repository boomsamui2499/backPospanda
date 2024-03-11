<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Out_of_stock extends Model
{
    use HasFactory;
    protected $primaryKey = 'out_of_stock_id';
    protected $table = 'out_of_stock';
    protected $fillable = [
        'out_of_stock_id',
        'product_id',
        'out_of_stock_qty',
        'active'
    ];
    protected $casts = [
        'out_of_stock_id' => 'integer',
        'out_of_stock_qty' => 'float',
        'product_id' => 'integer',
       
    ];
    public function product()
    {
        return $this->belongsTo(Product::class, "product_id")->where('active', 1)->with('product_uom');
    }
}
