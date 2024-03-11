<?php


namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductPricelist extends Model
{
    use HasFactory;
    public $timestamps = false;
    protected $table = 'product_pricelist';
    protected $primaryKey = 'product_pricelist_id';

    protected $fillable = [
        "pricelist_id",
        "product_id",
        "price"
    ];
    protected $casts = [
        'product_pricelist_id' => 'integer',
        'price' => 'float',
    ];

    public function pricelist()
    {
        return $this->belongsTo(Pricelist::class, 'pricelist_id');
    }
}
