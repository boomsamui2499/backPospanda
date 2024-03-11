<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;
    protected $primaryKey = 'product_id';
    protected $table = 'product';
    protected $fillable = [
        'product_name',
        'price',
        'type',
        'barcode',
        'category_id',
        'stock_qty',
        'image',
        'current_average_cost'
    ];
    protected $casts = [
        'product_id' => 'integer',
        'category_id' => 'integer',
        'stock_qty' => 'float',
        'type' => 'integer',
        'price' => 'float',
        'current_average_cost' => 'float',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class, "category_id");
    }
    public function product_uom()
    {
        return $this->hasMany(Product_uom::class, "product_id")->where('active', 1);
    }
    public function productPricelist()
    {
        return $this->hasMany(ProductPricelist::class, "product_id")->where('active', 1)->with("pricelist");
    }
    public function productPricelistByPricelistId($pricelist_id)
    {
        return $this->hasMany(ProductPricelist::class, "product_id")->where('active', 1)->where('pricelist_id', $pricelist_id);
    }
}
