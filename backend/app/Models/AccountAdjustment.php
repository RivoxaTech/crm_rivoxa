<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AccountAdjustment extends Model
{
    protected $guarded = [];
    protected $casts = ['amount' => 'float', 'adjusted_on' => 'date:Y-m-d'];
}
