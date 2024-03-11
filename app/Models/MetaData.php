<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MetaData extends Model
{
    use HasFactory;
    protected $primaryKey = 'meta_id';
    protected $table = 'metaData';
    protected $fillable = [
        'meta_id',
        'meta_module',
        'meta_key',
        'meta_value',
        'active',

    ];
    protected $casts = [
        'meta_id' => 'integer',
      
    ];
}
