<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class tabDefault extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'type',
        'amount_1',
        'amount_2',
    ];
}
