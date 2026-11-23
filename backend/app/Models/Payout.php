<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payout extends Model
{
    protected $guarded = [];
    protected $casts = ['amount' => 'float', 'paid_on' => 'date:Y-m-d'];

    public function share(): BelongsTo
    {
        return $this->belongsTo(ProjectShare::class, 'project_share_id');
    }

    public function partner(): BelongsTo
    {
        return $this->belongsTo(Partner::class);
    }
}
