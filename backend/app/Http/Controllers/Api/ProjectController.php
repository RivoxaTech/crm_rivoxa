<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Partner;
use App\Models\Project;
use App\Models\ProjectShare;
use App\Services\ItemWriter;
use App\Services\ProfitSplitter;
use App\Services\ProjectFinance;
use App\Services\RecurringBilling;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProjectController extends Controller
{
    private function rules(): array
    {
        return [
            'client_id' => 'required|exists:clients,id',
            'title' => 'required|string|max:160',
            'description' => 'nullable|string|max:255',
            'type' => 'required|in:one_time,monthly',
            'split_mode' => 'required|in:half,equal',
            'start_date' => 'nullable|date',
            'notes' => 'nullable|string|max:2000',
            'auto_generate' => 'boolean',
            'billing_day' => 'nullable|integer|min:1|max:28',
            'recurring' => 'nullable|array',
            'recurring.*.name' => 'required|string|max:160',
            'recurring.*.description' => 'nullable|string|max:300',
            'recurring.*.unit_price' => 'required|numeric|min:0',
            'recurring.*.cost' => 'nullable|numeric|min:0',
        ];
    }

    public function index(Request $request)
    {
        return Project::with('client:id,name')
            ->withSum('items as billed', 'amount')->withSum('payments as paid', 'amount')->withSum('expenses as expenses_total', 'amount')
            ->when($request->status, fn ($q, $s) => $q->where('status', $s))
            ->when($request->client_id, fn ($q, $c) => $q->where('client_id', $c))
            ->when($request->q, fn ($q, $s) => $q->where(fn ($w) => $w->where('title', 'like', "%$s%")->orWhereHas('client', fn ($c) => $c->where('name', 'like', "%$s%"))))
            ->latest('id')->get()
            ->map(fn ($p) => $p->toArray() + ['balance' => round((float) $p->billed - (float) $p->paid, 2)]);
    }

    public function store(Request $request)
    {
        $data = $request->validate($this->rules() + ['items' => 'nullable|array'] + ItemWriter::rules('items.*.'));

        $project = DB::transaction(function () use ($data) {
            $project = Project::create(collect($data)->except('items')->all() + ['start_date' => $data['start_date'] ?? now()->toDateString()]);
            foreach ($data['items'] ?? [] as $item) {
                ItemWriter::save($project, $item);
            }

            return $project;
        });

        return $this->show($project);
    }

    public function show(Project $project)
    {
        $summary = ProjectFinance::summary($project);
        $partners = Partner::orderBy('id')->pluck('name', 'id')->all();

        return [
            'project' => $project->load('client'),
            'items' => $project->items,
            'payments' => $project->payments,
            'expenses' => $project->expenses()->with('partner:id,name')->get(),
            'invoices' => $project->invoices,
            'shares' => $project->shares()->with('partner:id,name')->get(),
            'summary' => $summary,
            'split_preview' => ProfitSplitter::split($summary['profit'], $project->split_mode, $partners),
        ];
    }

    public function update(Request $request, Project $project)
    {
        abort_if($project->isClosed(), 422, 'This project is closed. Reopen it to make changes.');
        $project->update($request->validate($this->rules()));

        return $this->show($project);
    }

    public function destroy(Project $project)
    {
        abort_if($project->isClosed(), 422, 'Closed projects with payouts cannot be deleted. Reopen it first.');
        $project->delete();

        return response()->noContent();
    }

    public function close(Project $project)
    {
        $summary = ProjectFinance::summary($project);
        if (! $summary['can_close']) {
            return response()->json(['message' => 'This project cannot be closed yet.', 'errors' => ['project' => $summary['blockers']]], 422);
        }

        DB::transaction(function () use ($project, $summary) {
            $rows = ProfitSplitter::split($summary['profit'], $project->split_mode, Partner::orderBy('id')->pluck('name', 'id')->all());
            foreach ($rows as $row) {
                ProjectShare::create([
                    'project_id' => $project->id, 'partner_id' => $row['partner_id'], 'party' => $row['party'],
                    'percent' => $row['percent'], 'amount' => $row['amount'],
                ]);
            }
            $project->update([
                'status' => 'closed', 'closed_at' => now(),
                'final_revenue' => $summary['billed'], 'final_expenses' => $summary['expenses'], 'final_profit' => $summary['profit'],
            ]);
        });

        return $this->show($project);
    }

    public function reopen(Project $project)
    {
        abort_unless($project->isClosed(), 422, 'This project is not closed.');
        abort_if($project->shares()->where('paid_amount', '>', 0)->exists(), 422, 'Partner payouts were already made for this project, so it cannot be reopened.');

        DB::transaction(function () use ($project) {
            $project->shares()->delete();
            $project->update(['status' => 'active', 'closed_at' => null, 'final_revenue' => null, 'final_expenses' => null, 'final_profit' => null]);
        });

        return $this->show($project);
    }

    /** Bill one month of a monthly project (also used by the daily scheduler). */
    public function billPeriod(Request $request, Project $project)
    {
        $data = $request->validate(['period' => 'required|date_format:Y-m']);
        RecurringBilling::generate($project, $data['period']);

        return $this->show($project);
    }
}
