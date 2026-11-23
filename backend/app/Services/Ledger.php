<?php

namespace App\Services;

use App\Models\AccountAdjustment;
use App\Models\Expense;
use App\Models\Payment;
use App\Models\Payout;
use Illuminate\Support\Collection;

/** The company account is computed from source records, so it can never drift out of sync. */
class Ledger
{
    public static function entries(): Collection
    {
        $rows = collect();

        Payment::with('project.client')->get()->each(fn ($p) => $rows->push([
            'date' => $p->paid_at->toDateString(), 'sort' => 1, 'id' => "pay-$p->id", 'type' => 'in', 'kind' => 'client_payment',
            'title' => 'Payment from '.$p->project->client->name, 'detail' => $p->project->title.($p->label ? ' · '.$p->label : ''),
            'amount' => $p->amount, 'project_id' => $p->project_id,
        ]));

        Expense::with('project', 'partner')->get()->each(function ($e) use ($rows) {
            $project = $e->project?->title;
            if ($e->paid_by === 'company') {
                $rows->push([
                    'date' => $e->spent_on->toDateString(), 'sort' => 2, 'id' => "exp-$e->id", 'type' => 'out',
                    'kind' => $e->project_id ? 'project_expense' : 'company_expense', 'title' => $e->title,
                    'detail' => ($project ? "$project · " : '').$e->reason, 'amount' => $e->amount, 'project_id' => $e->project_id,
                ]);
            } elseif ($e->reimbursed_on) {
                $rows->push([
                    'date' => $e->reimbursed_on->toDateString(), 'sort' => 3, 'id' => "rei-$e->id", 'type' => 'out', 'kind' => 'reimbursement',
                    'title' => 'Reimbursed '.($e->partner?->name ?? 'partner'), 'detail' => $e->title.' · '.$e->reason,
                    'amount' => $e->amount, 'project_id' => $e->project_id,
                ]);
            }
        });

        Payout::with('partner', 'share.project')->get()->each(fn ($o) => $rows->push([
            'date' => $o->paid_on->toDateString(), 'sort' => 4, 'id' => "out-$o->id", 'type' => 'out', 'kind' => 'payout',
            'title' => 'Profit payout to '.$o->partner->name, 'detail' => (string) $o->share?->project?->title,
            'amount' => $o->amount, 'project_id' => $o->share?->project_id,
        ]));

        AccountAdjustment::all()->each(fn ($a) => $rows->push([
            'date' => $a->adjusted_on->toDateString(), 'sort' => 0, 'id' => "adj-$a->id", 'type' => $a->type === 'deposit' ? 'in' : 'out',
            'kind' => 'adjustment', 'title' => $a->type === 'deposit' ? 'Money added' : 'Money taken out', 'detail' => $a->note,
            'amount' => $a->amount, 'project_id' => null,
        ]));

        $balance = 0.0;

        return $rows->sortBy([['date', 'asc'], ['sort', 'asc'], ['id', 'asc']])->values()->map(function ($r) use (&$balance) {
            $balance = round($balance + ($r['type'] === 'in' ? $r['amount'] : -$r['amount']), 2);

            return $r + ['balance' => $balance];
        });
    }

    public static function balance(): float
    {
        return (float) (self::entries()->last()['balance'] ?? 0);
    }
}
