<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pricelist extends Model
{
    use HasFactory;
    public $timestamps = false;
    protected $table = 'pricelist';
    protected $primaryKey = 'pricelist_id';

    protected $fillable = [
        "pricelist_id",
        "pricelist_name",
        "created_datetime"
    ];    protected $casts = [
        'pricelist_id' => 'integer',
    ];
}
