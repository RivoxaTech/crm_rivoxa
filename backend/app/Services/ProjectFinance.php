<?php

namespace App\Services;

use App\Models\Project;
use App\Support\Money;

class ProjectFinance
{
    public static function summary(Project $p): array
    {
        $billed = round((float) $p->items()->sum('amount'), 2);
        $paid = round((float) $p->payments()->sum('amount'), 2);
        $expenses = round((float) $p->expenses()->sum('amount'), 2);
        $balance = round($billed - $paid, 2);
        $pending = $p->items()->whereNull('amount')->count();
        $unreimbursed = $p->expenses()->where('paid_by', 'partner')->whereNull('reimbursed_on')->count();

        $blockers = [];
        if (! $p->items()->exists()) {
            $blockers[] = 'Add at least one item before closing.';
        }
        if ($pending) {
            $blockers[] = "$pending item(s) still waiting for the final amount (set the quantity or amount).";
        }
        if ($balance > 0) {
            $blockers[] = 'Client still owes '.Money::rs($balance).'.';
        } elseif ($balance < 0) {
            $blockers[] = 'Payments exceed the total by '.Money::rs(-$balance).'. Fix the payments or add an item.';
        }
        if ($unreimbursed) {
            $blockers[] = "$unreimbursed partner expense(s) not yet reimbursed from the company account.";
        }

        return [
            'billed' => $billed, 'paid' => $paid, 'balance' => $balance,
            'expenses' => $expenses, 'profit' => round($billed - $expenses, 2),
            'pending_items' => $pending, 'unreimbursed' => $unreimbursed,
            'blockers' => $blockers, 'can_close' => ! $p->isClosed() && $blockers === [],
        ];
    }
}
