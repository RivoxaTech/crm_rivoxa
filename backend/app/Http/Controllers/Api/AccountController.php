<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AccountAdjustment;
use App\Models\Expense;
use App\Models\ProjectShare;
use App\Services\Ledger;
use Illuminate\Http\Request;

class AccountController extends Controller
{
    public function show()
    {
        $entries = Ledger::entries();
        $unpaidShares = (float) ProjectShare::where('party', 'partner')->sum('amount') - (float) ProjectShare::where('party', 'partner')->sum('paid_amount');
        $owedExpenses = (float) Expense::where('paid_by', 'partner')->whereNull('reimbursed_on')->sum('amount');
        $balance = (float) ($entries->last()['balance'] ?? 0);

        return [
            'balance' => $balance,
            'total_in' => round((float) $entries->where('type', 'in')->sum('amount'), 2),
            'total_out' => round((float) $entries->where('type', 'out')->sum('amount'), 2),
            'owed_partners' => round($unpaidShares + $owedExpenses, 2),
            'entries' => $entries->reverse()->values(),
        ];
    }

    public function adjust(Request $request)
    {
        return AccountAdjustment::create($request->validate([
            'type' => 'required|in:deposit,withdrawal', 'amount' => 'required|numeric|gt:0',
            'adjusted_on' => 'required|date', 'note' => 'required|string|max:255',
        ]));
    }

    public function destroyAdjustment(AccountAdjustment $adjustment)
    {
        $adjustment->delete();

        return response()->noContent();
    }
}
