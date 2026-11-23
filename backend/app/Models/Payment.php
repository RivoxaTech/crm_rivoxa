<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    protected $guarded = [];
    protected $casts = ['amount' => 'float', 'paid_at' => 'date:Y-m-d'];
    protected $hidden = ['receipt_path'];
    protected $appends = ['has_receipt'];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function getHasReceiptAttribute(): bool
    {
        return (bool) $this->receipt_path;
    }
}
