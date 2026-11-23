<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Invoice extends Model
{
    protected $guarded = [];
    protected $casts = [
        'snapshot' => 'array', 'issue_date' => 'date:Y-m-d',
        'subtotal' => 'float', 'paid' => 'float', 'balance' => 'float',
        'whatsapp_at' => 'datetime', 'emailed_at' => 'datetime',
    ];
    protected $hidden = ['token', 'snapshot'];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public static function nextNumber(string $issueDate): string
    {
        $year = substr($issueDate, 0, 4);
        $prefix = Setting::get('invoice_prefix', 'RVX')."-$year-";
        $last = self::where('number', 'like', $prefix.'%')->orderByDesc('number')->value('number');
        $next = $last ? ((int) substr($last, strlen($prefix))) + 1 : 1;

        return $prefix.str_pad((string) $next, 3, '0', STR_PAD_LEFT);
    }
}
