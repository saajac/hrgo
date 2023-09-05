<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ListImpot extends Model
{
    use HasFactory;

    protected $fillable = [
        'tranche_basse',
        'tranche_haute',
        'montant',
    ];
}
