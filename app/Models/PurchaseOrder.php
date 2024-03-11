<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseOrder extends Model
{
    use HasFactory;
    protected $primaryKey = 'purchase_order_id';
    protected $table = 'purchase_order';
    protected $fillable = [
        'purchase_order_number',
        'user_id',
        'create_datetime',
        'supplier_id',
        'comment',
        'subtotal',
        'tax',
        'total',
        'active',
        'status'
    ];
    protected $casts = [
        'purchase_order_id' => 'integer',
        'user_id' => 'integer',
        'supplier_id' => 'integer',
        'status' => 'integer',
        'subtotal' => 'float',
        'total' => 'float',
        'tax' => 'float',
    ];
    public function supplier()
    {
        return $this->belongsTo(Supplier::class, "supplier_id")->where('active', 1);
    }

    public function user()
    {
        return $this->belongsTo(User::class, "user_id")->where('active', 1);
    }

    public function purchase_order_line()
    {
        return $this->hasMany(PurchaseOrderLine::class, "purchase_order_id")->where('active', 1)->with('product')->with('uom');
    }
}
