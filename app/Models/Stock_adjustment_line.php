<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Stock_adjustment_line extends Model
{
    use HasFactory;
    protected $primaryKey = 'stock_adjustment_line_id';
    protected $table = 'stock_adjustment_line';
    protected $fillable = [
        'product_id',
        'stock_adjustment_id',
        'computed_qty',
        'real_qty',
        'different_qty',
        'create_datetime',
        'active'
    ];
    protected $casts = [
        'stock_adjustment_line_id' => 'integer',
        'product_id' => 'integer',
        'stock_adjustment_id' => 'integer',

    ];
    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}
