<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClientService extends Model
{
    protected $guarded = [];
    protected $casts = [
        'purchased_on' => 'date:Y-m-d', 'expires_on' => 'date:Y-m-d',
        'renewal_price' => 'float', 'renewal_cost' => 'float',
    ];
    protected $appends = ['days_left', 'state'];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function getDaysLeftAttribute(): ?int
    {
        return $this->expires_on ? (int) now()->startOfDay()->diffInDays($this->expires_on->startOfDay(), false) : null;
    }

    /** expired | soon | ok | none */
    public function getStateAttribute(): string
    {
        $d = $this->days_left;
        if ($d === null) {
            return 'none';
        }

        return $d < 0 ? 'expired' : ($d <= (int) Setting::get('renewal_alert_days', '30') ? 'soon' : 'ok');
    }
}
