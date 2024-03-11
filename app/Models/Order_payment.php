<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order_payment extends Model
{
    use HasFactory;
    protected $primaryKey = 'order_payment_id';
    protected $table = 'order_payment';
    protected $fillable = [
        'price',
        'payment_id',
        'order_id',
        'active'
    ];
    protected $casts = [
        'order_payment_id' => 'integer',
        'payment_id' => 'integer',
        'order_id' => 'integer',
        'qty' => 'integer',
        'price' => 'float',
    ];
    public function payment()
    {
        return $this->belongsTo(Payment::class, "payment_id");
    }
}
