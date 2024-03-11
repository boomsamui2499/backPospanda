<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Member extends Model
{
    use HasFactory;
    protected $table = 'member';
    protected $primaryKey = 'member_id';

    protected $fillable = [

        'Firstname',
        'Lastname',
        'Nickname',
        'gender',
        'phone_number',
        'member_code',
        'birthdate',
        'registered_date',
        'address_line1',
        'address_line2',
        'province',
        'zip_code',
        'debt',
        'loyalty_point',
        'line_id',
        'active'

    ];
    protected $casts = [
        'member_id' => 'integer',
        'loyalty_point' => 'integer',
        'debt' => 'float',
    ];
}
