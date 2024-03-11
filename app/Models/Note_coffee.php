<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Note_coffee extends Model
{
    use HasFactory;
    use HasFactory;
    protected $table = 'note_coffee';
    protected $primaryKey = 'note_coffee_id';

    protected $fillable = [

        'note'

    ];

}
