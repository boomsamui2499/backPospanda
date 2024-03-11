<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Expiration_log extends Model
{
    use HasFactory;
    protected $table = 'expiration_log';
    protected $primaryKey = 'expiration_log_id';

    protected $fillable = [
        'expiration_log_id',
        'product_id',
        'lot_number',
        'created_datetime',
        'expired_datetime',
        'active'
    ];
    protected $casts = [
        'expiration_log_id' => 'integer',
        'product_id' => 'integer',

    ];
    public function product()
    {
        return $this->belongsTo (Product::class, "product_id")->where('active', 1);
    }
}
