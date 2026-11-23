<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CatalogService extends Model
{
    protected $guarded = [];
    protected $casts = ['unit_price' => 'float', 'cost' => 'float'];
}
