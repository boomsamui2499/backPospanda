<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pos_pin extends Model
{
    use HasFactory;
    protected $primaryKey = 'pos_pin_id';
    protected $table = 'pos_pin';
    protected $fillable = [
        'pos_pin_id',
        'product_id',
        'sequence',
        'active'
    ];
    protected $casts = [
        'pos_pin_id' => 'integer',
        'product_id' => 'integer',
        'sequence' => 'integer',
       
    ];
    public function product()
    {
        return $this->belongsTo(Product::class, "product_id")->where('active', 1)->with('product_uom')->with('productPricelist');
    }
    // public function product_uom()
    // {
    //     return $this->hasMany(Product_uom::class, "product_id")->where('active', 1);
    // }
    public function productPricelist()
    {
        return $this->hasMany(ProductPricelist::class, "product_id")->where('active', 1)->with('pricelist');
    }
    public function productPricelistByPricelistId($pricelist_id)
    {
        return $this->hasMany(ProductPricelist::class, "product_id")->where('active', 1)->where('pricelist_id', $pricelist_id);
    }
}
