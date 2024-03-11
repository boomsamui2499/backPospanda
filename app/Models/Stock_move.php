<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Stock_move extends Model
{
    use HasFactory;
    protected $primaryKey = 'stock_move_id';
    protected $table = 'stock_move';
    protected $fillable = [
        'product_id',
        'ref_type',
        'ref_id',
        'qty',
        'create_datetime',
        'updated_at'
    ];
    protected $casts = [
        'stock_move_id' => 'integer',
        'product_id' => 'integer',
        'ref_id' => 'integer',
        'qty' => 'integer',
    ];
}
