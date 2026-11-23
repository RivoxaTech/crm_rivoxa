<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Expense;
use App\Models\Project;
use Illuminate\Http\Request;

class ExpenseController extends Controller
{
    private function rules(): array
    {
        return [
            'project_id' => 'nullable|exists:projects,id', 'title' => 'required|string|max:160', 'reason' => 'required|string|max:500',
            'category' => 'nullable|string|max:60', 'amount' => 'required|numeric|gt:0', 'spent_on' => 'required|date',
            'paid_by' => 'required|in:company,partner', 'partner_id' => 'required_if:paid_by,partner|nullable|exists:partners,id',
        ];
    }

    private function guard(?int $projectId): void
    {
        if ($projectId && Project::find($projectId)?->isClosed()) {
            abort(422, 'This project is closed. Reopen it to make changes.');
        }
    }

    private function clean(array $d): array
    {
        if (($d['paid_by'] ?? '') === 'company') {
            $d['partner_id'] = null;
        }

        return $d;
    }

    public function index(Request $request)
    {
        return Expense::with('partner:id,name', 'project:id,title')
            ->when($request->scope === 'company', fn ($q) => $q->whereNull('project_id'))
            ->latest('spent_on')->latest('id')->get();
    }

    public function store(Request $request)
    {
        $data = $this->clean($request->validate($this->rules()));
        $this->guard($data['project_id'] ?? null);

        return Expense::create($data);
    }

    public function update(Request $request, Expense $expense)
    {
        $this->guard($expense->project_id);
        abort_if($expense->reimbursed_on, 422, 'This expense was already reimbursed. Undo the reimbursement to edit it.');
        $expense->update($this->clean($request->validate($this->rules())));

        return $expense;
    }

    public function destroy(Expense $expense)
    {
        $this->guard($expense->project_id);
        $expense->delete();

        return response()->noContent();
    }

    /** Company pays the partner back for an expense they covered personally. */
    public function reimburse(Request $request, Expense $expense)
    {
        abort_unless($expense->paid_by === 'partner', 422, 'Only partner-paid expenses can be reimbursed.');
        $data = $request->validate(['date' => 'nullable|date']);
        $expense->update(['reimbursed_on' => $data['date'] ?? now()->toDateString()]);

        return $expense;
    }

    public function undoReimburse(Expense $expense)
    {
        $this->guard($expense->project_id);
        $expense->update(['reimbursed_on' => null]);

        return $expense;
    }
}
