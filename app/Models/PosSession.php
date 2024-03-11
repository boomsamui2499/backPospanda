<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PosSession extends Model
{
    use HasFactory;
    protected $table = 'pos_session';
    protected $primaryKey = 'pos_session_id';

    protected $fillable = [
        'pos_session_id',
        'pos_session_name',
        'user_id',
        'open_datetime',
        'close_datetime',
        'open_cash_amount',
        'close_cash_amount'
    ];
    protected $casts = [
        'pos_session_id' => 'integer',
        'user_id' => 'integer',
        'open_cash_amount' => 'float',
        'close_cash_amount' => 'float',
    ];
}
