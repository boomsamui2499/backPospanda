<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Orders extends Model
{
    use HasFactory;
    protected $primaryKey = 'order_id';
    protected $table = 'orders';
    protected $fillable = [
        'order_id',
        'order_number',
        'subtotal',
        'vat',
        'total',
        'total_payment',
        'total_recive',
        'total_margin',
        'price_change',
        'created_datetime',
        'is_vat',
        'type',
        'active'
    ];
    protected $casts = [
        'order_id' => 'integer',
        'type' => 'integer',
        'is_vat' => 'integer',
        'subtotal' => 'float',
        'vat' => 'float',
        'total' => 'float',
        'total_payment' => 'float',
        'total_recive' => 'float',
        'total_margin' => 'float',
        'price_change' => 'float',
        'vat' => 'float',
    ];
    public function user()
    {
        return $this->belongsTo(User::class, "user_id");
    }
    public function member()
    {
        return $this->belongsTo(Member::class, "member_id");
    }
    public function possesion()
    {
        return $this->belongsTo(PosSession::class, "pos_session_id");
    }
    public function orderpayment()
    {
        return $this->hasMany(Order_payment::class, "order_id")->where('active', 1)->with("payment");
    }
    public function orderline()
    {
        return $this->hasMany(Order_lines::class, "order_id")->where('active', 1)->with("product")->with("uom")->with("promotion");
    }
}
