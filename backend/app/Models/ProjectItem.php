<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProjectItem extends Model
{
    protected $guarded = [];
    protected $casts = [
        'unit_price' => 'float', 'quantity' => 'float', 'amount' => 'float', 'cost' => 'float',
        'is_free' => 'boolean', 'is_addon' => 'boolean', 'added_on' => 'date:Y-m-d',
    ];
}
