<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Stock_adjustment extends Model
{
    use HasFactory;
    protected $primaryKey = 'stock_adjustment_id';
    protected $table = 'stock_adjustment';
    protected $fillable = [
        'stock_adjustment_name',
        'user_id',
        'create_datetime',
        'type',
        'active'
    ];
    protected $casts = [
        'stock_adjustment_id' => 'integer',
        'user_id' => 'integer',
        'type' => 'integer',
    ];
    public function user()
    {
        return $this->belongsTo(User::class, "user_id")->where('active', 1);
    }

    public function stock_adjustment_line()
    {
        return $this->hasMany(Stock_adjustment_line::class, "stock_adjustment_id")->where('active', 1)->with('product');
    }}
