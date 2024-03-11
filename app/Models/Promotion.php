<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Promotion extends Model
{
    use HasFactory;
    protected $table = 'promotion';
    protected $primaryKey = 'promotion_id';

    protected $fillable = [
        'promotion_id',
        'promotion_name',
        'product_id',
        'price',
        'point',
        'qty',

    ];

    protected $casts = [
        'promotion_id' => 'integer',
        'product_id' => 'integer',
        'price' => 'float',
        'point' => 'integer',
        'qty' => 'float',
        'active' => 'integer',
    ];
    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}
