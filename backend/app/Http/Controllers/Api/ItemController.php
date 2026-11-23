<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Expense;
use App\Models\Project;
use App\Models\ProjectItem;
use App\Services\ItemWriter;
use Illuminate\Http\Request;

class ItemController extends Controller
{
    public function store(Request $request, Project $project)
    {
        abort_if($project->isClosed(), 422, 'This project is closed. Reopen it to make changes.');

        return ItemWriter::save($project, $request->validate(ItemWriter::rules()));
    }

    public function update(Request $request, ProjectItem $item)
    {
        $project = Project::findOrFail($item->project_id);
        abort_if($project->isClosed(), 422, 'This project is closed. Reopen it to make changes.');

        return ItemWriter::save($project, $request->validate(ItemWriter::rules()), $item);
    }

    public function destroy(ProjectItem $item)
    {
        abort_if(Project::findOrFail($item->project_id)->isClosed(), 422, 'This project is closed. Reopen it to make changes.');
        Expense::where('project_item_id', $item->id)->whereNull('reimbursed_on')->delete();
        $item->delete();

        return response()->noContent();
    }
}
