<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Project extends Model
{
    protected $guarded = [];
    protected $casts = [
        'start_date' => 'date:Y-m-d',
        'closed_at' => 'datetime:Y-m-d',
        'recurring' => 'array',
        'auto_generate' => 'boolean',
        'final_revenue' => 'float',
        'final_expenses' => 'float',
        'final_profit' => 'float',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(ProjectItem::class)->orderBy('sort')->orderBy('id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class)->orderBy('paid_at')->orderBy('id');
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class)->orderBy('spent_on')->orderBy('id');
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class)->orderByDesc('id');
    }

    public function shares(): HasMany
    {
        return $this->hasMany(ProjectShare::class)->orderBy('id');
    }

    public function isClosed(): bool
    {
        return $this->status === 'closed';
    }
}
