<?php

namespace App\Services;

use App\Models\Expense;
use App\Models\Project;
use App\Models\ProjectItem;

/** Creates/updates a project line and keeps its cost in sync as an expense. */
class ItemWriter
{
    public static function rules(string $prefix = ''): array
    {
        $rules = [
            'name' => 'required|string|max:160',
            'description' => 'nullable|string|max:300',
            'short_label' => 'nullable|string|max:80',
            'rate_text' => 'nullable|string|max:60',
            'kind' => 'required|string|max:80',
            'billing' => 'required|in:fixed,per_unit',
            'unit' => 'nullable|string|max:30',
            'unit_price' => 'required|numeric|min:0',
            'quantity' => 'nullable|numeric|min:0',
            'amount' => 'nullable|numeric|min:0',
            'cost' => 'required|numeric|min:0',
            'is_free' => 'boolean',
            'is_addon' => 'boolean',
            'added_on' => 'nullable|date',
            'period' => 'nullable|string|size:7',
        ];

        $out = [];
        foreach ($rules as $key => $rule) {
            $out[$prefix.$key] = $rule;
        }

        return $out;
    }

    /** null amount means "decided on delivery" */
    public static function amount(array $d): ?float
    {
        if (! empty($d['is_free'])) {
            return 0.0;
        }
        if (isset($d['amount'])) {
            return round((float) $d['amount'], 2);
        }
        if (($d['billing'] ?? 'fixed') === 'fixed') {
            return round((float) $d['unit_price'], 2);
        }

        return isset($d['quantity']) ? round((float) $d['unit_price'] * (float) $d['quantity'], 2) : null;
    }

    public static function save(Project $project, array $d, ?ProjectItem $item = null): ProjectItem
    {
        $addedOn = $d['added_on'] ?? ($item?->added_on?->toDateString() ?? now()->toDateString());
        $period = $d['period'] ?? $item?->period;
        if (! $period && $project->type === 'monthly') {
            $period = substr($addedOn, 0, 7);
        }

        $item ??= new ProjectItem(['project_id' => $project->id, 'sort' => (int) $project->items()->max('sort') + 1]);
        $item->fill([
            'kind' => $d['kind'], 'name' => $d['name'], 'description' => $d['description'] ?? null,
            'short_label' => $d['short_label'] ?? null, 'rate_text' => $d['rate_text'] ?? null,
            'billing' => $d['billing'], 'unit' => $d['unit'] ?? null, 'unit_price' => $d['unit_price'],
            'quantity' => $d['quantity'] ?? null, 'amount' => self::amount($d),
            'cost' => $d['cost'] ?? 0, 'is_free' => ! empty($d['is_free']), 'is_addon' => ! empty($d['is_addon']),
            'period' => $period, 'added_on' => $addedOn,
        ])->save();

        self::syncCost($project, $item);

        return $item;
    }

    public static function syncCost(Project $project, ProjectItem $item): void
    {
        $linked = Expense::where('project_item_id', $item->id)->first();

        if ($item->cost > 0) {
            Expense::updateOrCreate(['project_item_id' => $item->id], [
                'project_id' => $project->id, 'title' => $item->name, 'reason' => 'Cost of '.$item->name,
                'category' => $item->kind, 'amount' => $item->cost, 'spent_on' => $item->added_on ?? now(),
            ]);
        } elseif ($linked && ! $linked->reimbursed_on) {
            $linked->delete();
        }
    }
}
