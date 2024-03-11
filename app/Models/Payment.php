<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;
    protected $table = 'payment';
    protected $primaryKey = 'payment_id';

    protected $fillable = [

        'payment_name',
        'special_type',
        'is_special_payment'

    ];
    protected $casts = [
        'payment_id' => 'integer',
    ];
}
