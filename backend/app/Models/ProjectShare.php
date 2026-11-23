<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProjectShare extends Model
{
    protected $guarded = [];
    protected $casts = ['percent' => 'float', 'amount' => 'float', 'paid_amount' => 'float'];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function partner(): BelongsTo
    {
        return $this->belongsTo(Partner::class);
    }

    public function payouts(): HasMany
    {
        return $this->hasMany(Payout::class);
    }

    public function remaining(): float
    {
        return round($this->amount - $this->paid_amount, 2);
    }
}
