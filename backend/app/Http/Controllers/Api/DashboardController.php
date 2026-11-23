<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ClientService;
use App\Models\Expense;
use App\Models\Payment;
use App\Models\Project;
use App\Models\ProjectItem;
use App\Models\ProjectShare;
use App\Services\Ledger;

class DashboardController extends Controller
{
    public function index()
    {
        $monthStart = now()->startOfMonth();
        $from = $monthStart->copy()->subMonths(11);

        $months = [];
        for ($i = 0; $i < 12; $i++) {
            $m = $from->copy()->addMonths($i);
            $months[$m->format('Y-m')] = ['label' => $m->format('M'), 'income' => 0.0, 'expenses' => 0.0, 'profit' => 0.0];
        }
        foreach (Payment::where('paid_at', '>=', $from->toDateString())->get(['amount', 'paid_at']) as $p) {
            if (isset($months[$k = $p->paid_at->format('Y-m')])) {
                $months[$k]['income'] += $p->amount;
            }
        }
        foreach (Expense::where('spent_on', '>=', $from->toDateString())->get(['amount', 'spent_on']) as $e) {
            if (isset($months[$k = $e->spent_on->format('Y-m')])) {
                $months[$k]['expenses'] += $e->amount;
            }
        }
        foreach (Project::where('status', 'closed')->where('closed_at', '>=', $from)->get(['final_profit', 'closed_at']) as $p) {
            if (isset($months[$k = $p->closed_at->format('Y-m')])) {
                $months[$k]['profit'] += $p->final_profit;
            }
        }

        $active = Project::where('status', 'active')
            ->withSum('items as billed', 'amount')->withSum('payments as paid', 'amount')->withSum('expenses as expenses_total', 'amount')
            ->with('client:id,name')->get();

        $received = fn ($start, $end) => round((float) Payment::whereBetween('paid_at', [$start->toDateString(), $end->toDateString()])->sum('amount'), 2);
        $unpaidShares = (float) ProjectShare::where('party', 'partner')->sum('amount') - (float) ProjectShare::where('party', 'partner')->sum('paid_amount');
        $owedExpenses = (float) Expense::where('paid_by', 'partner')->whereNull('reimbursed_on')->sum('amount');

        return [
            'kpi' => [
                'received_month' => $received($monthStart, now()),
                'received_prev' => $received($monthStart->copy()->subMonth(), $monthStart->copy()->subDay()),
                'outstanding' => round($active->sum(fn ($p) => max($p->billed - $p->paid, 0)), 2),
                'profit_realized' => round((float) Project::where('status', 'closed')->sum('final_profit'), 2),
                'profit_expected' => round($active->sum(fn ($p) => $p->billed - $p->expenses_total), 2),
                'company_balance' => Ledger::balance(),
                'owed_partners' => round($unpaidShares + $owedExpenses, 2),
                'active_projects' => $active->count(),
                'closed_projects' => Project::where('status', 'closed')->count(),
            ],
            'months' => array_values(array_map(fn ($m) => array_map(fn ($v) => is_float($v) ? round($v, 2) : $v, $m), $months)),
            'by_kind' => ProjectItem::selectRaw('kind, SUM(amount) as total')->groupBy('kind')->get()->map(fn ($r) => ['kind' => $r->kind, 'total' => round((float) $r->total, 2)])->filter(fn ($r) => $r['total'] > 0)->values(),
            'renewals' => ClientService::with('client:id,name')->whereNotNull('expires_on')->orderBy('expires_on')->get()->filter(fn ($s) => in_array($s->state, ['expired', 'soon']))->values(),
            'top_balances' => $active->filter(fn ($p) => $p->billed - $p->paid > 0)->sortByDesc(fn ($p) => $p->billed - $p->paid)->take(6)->map(fn ($p) => [
                'id' => $p->id, 'title' => $p->title, 'client' => $p->client->name, 'billed' => (float) $p->billed, 'paid' => (float) $p->paid, 'balance' => round($p->billed - $p->paid, 2),
            ])->values(),
            'recent_payments' => Payment::with('project.client')->latest('paid_at')->latest('id')->limit(7)->get()->map(fn ($p) => [
                'id' => $p->id, 'project_id' => $p->project_id, 'client' => $p->project->client->name, 'project' => $p->project->title,
                'amount' => $p->amount, 'paid_at' => $p->paid_at->toDateString(), 'method' => $p->method,
            ]),
        ];
    }
}
