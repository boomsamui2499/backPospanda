<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Supplier extends Model
{
    use HasFactory;
    protected $table = 'supplier';
    protected $primaryKey = 'supplier_id';

    protected $fillable = [

        'Firstname',
        'Lastname',
        'Nickname',
        'gender',
        'phone_number',
        'registered_date',
        'address_line1',
        'address_line2',
        'province',
        'zip_code',
        'tax_registered_number',
        'company_name',
        'active'

    ];
    protected $casts = [
        'supplier_id' => 'integer',
    ];
}
