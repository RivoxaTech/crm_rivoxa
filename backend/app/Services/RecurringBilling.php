<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Project;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RecurringBilling
{
    public static function generate(Project $p, string $period): Invoice
    {
        if ($p->type !== 'monthly' || $p->isClosed()) {
            throw ValidationException::withMessages(['project' => 'Only active monthly projects can be billed by period.']);
        }
        if (empty($p->recurring)) {
            throw ValidationException::withMessages(['project' => 'Add the monthly lines to this project first.']);
        }
        if ($p->items()->where('period', $period)->exists()) {
            throw ValidationException::withMessages(['period' => 'This month is already billed.']);
        }

        return DB::transaction(function () use ($p, $period) {
            $label = date('M Y', strtotime($period.'-01'));
            foreach ($p->recurring as $line) {
                ItemWriter::save($p, [
                    'kind' => 'other', 'billing' => 'fixed', 'name' => $line['name'].' — '.$label,
                    'description' => $line['description'] ?? null, 'unit_price' => $line['unit_price'],
                    'cost' => $line['cost'] ?? 0, 'period' => $period, 'added_on' => now()->toDateString(),
                ]);
            }
            $invoice = InvoiceService::create($p, ['period' => $period, 'payment_terms' => 'Due on receipt']);
            $p->update(['last_billed_period' => $period]);

            return $invoice;
        });
    }
}
