<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ClientService;
use App\Models\Project;
use App\Services\ItemWriter;
use Illuminate\Http\Request;

class ClientServiceController extends Controller
{
    private function rules(): array
    {
        return [
            'client_id' => 'required|exists:clients,id', 'project_id' => 'nullable|exists:projects,id',
            'kind' => 'required|in:domain,hosting,other', 'name' => 'required|string|max:160', 'provider' => 'nullable|string|max:120',
            'purchased_on' => 'nullable|date', 'expires_on' => 'nullable|date', 'renewal_price' => 'nullable|numeric|min:0',
            'renewal_cost' => 'nullable|numeric|min:0', 'notes' => 'nullable|string|max:1000',
        ];
    }

    public function index()
    {
        return ClientService::with('client:id,name')->orderByRaw('expires_on is null')->orderBy('expires_on')->get();
    }

    public function store(Request $request)
    {
        return ClientService::create($request->validate($this->rules()));
    }

    public function update(Request $request, ClientService $service)
    {
        $service->update($request->validate($this->rules()));

        return $service;
    }

    public function destroy(ClientService $service)
    {
        $service->delete();

        return response()->noContent();
    }

    /** Extend the expiry; optionally open a one-time project so the renewal gets invoiced. */
    public function renew(Request $request, ClientService $service)
    {
        $data = $request->validate([
            'years' => 'required|integer|min:1|max:10', 'price' => 'nullable|numeric|min:0', 'cost' => 'nullable|numeric|min:0', 'bill' => 'boolean',
        ]);

        $from = $service->expires_on && $service->expires_on->isFuture() ? $service->expires_on->copy() : now();
        $service->update(['expires_on' => $from->addYears($data['years'])->toDateString()]);

        $projectId = null;
        if (! empty($data['bill'])) {
            $project = Project::create([
                'client_id' => $service->client_id, 'title' => 'Renewal — '.$service->name, 'description' => ucfirst($service->kind).' renewal',
                'type' => 'one_time', 'split_mode' => 'half', 'start_date' => now()->toDateString(),
            ]);
            ItemWriter::save($project, [
                'kind' => $service->kind, 'billing' => 'fixed', 'name' => ucfirst($service->kind).' renewal — '.$service->name,
                'description' => $data['years'].'-year renewal', 'unit_price' => $data['price'] ?? $service->renewal_price ?? 0,
                'cost' => $data['cost'] ?? $service->renewal_cost ?? 0,
            ]);
            $projectId = $project->id;
        }

        return ['service' => $service->fresh(), 'project_id' => $projectId];
    }
}
