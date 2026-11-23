<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Client;
use Illuminate\Http\Request;

class ClientController extends Controller
{
    private function rules(): array
    {
        return [
            'name' => 'required|string|max:160', 'contact_person' => 'nullable|string|max:120',
            'phone' => 'nullable|string|max:40', 'whatsapp' => 'nullable|string|max:40',
            'email' => 'nullable|email|max:160', 'address' => 'nullable|string|max:255', 'notes' => 'nullable|string|max:2000',
        ];
    }

    public function index(Request $request)
    {
        $clients = Client::query()
            ->when($request->q, fn ($q, $s) => $q->where(fn ($w) => $w->where('name', 'like', "%$s%")->orWhere('phone', 'like', "%$s%")->orWhere('contact_person', 'like', "%$s%")))
            ->with(['projects' => fn ($q) => $q->withSum('items as billed', 'amount')->withSum('payments as paid', 'amount')])
            ->orderBy('name')->get();

        return $clients->map(fn ($c) => $c->only(['id', 'name', 'contact_person', 'phone', 'email']) + [
            'projects' => $c->projects->count(),
            'active' => $c->projects->where('status', 'active')->count(),
            'billed' => round((float) $c->projects->sum('billed'), 2),
            'paid' => round((float) $c->projects->sum('paid'), 2),
            'balance' => round((float) $c->projects->where('status', 'active')->sum(fn ($p) => $p->billed - $p->paid), 2),
        ]);
    }

    public function show(Client $client)
    {
        $projects = $client->projects()->withSum('items as billed', 'amount')->withSum('payments as paid', 'amount')
            ->withSum('expenses as expenses_total', 'amount')->latest('id')->get()
            ->map(fn ($p) => $p->toArray() + ['balance' => round((float) $p->billed - (float) $p->paid, 2)]);

        return [
            'client' => $client,
            'projects' => $projects,
            'services' => $client->services()->orderBy('expires_on')->get(),
        ];
    }

    public function store(Request $request)
    {
        return Client::create($request->validate($this->rules()));
    }

    public function update(Request $request, Client $client)
    {
        $client->update($request->validate($this->rules()));

        return $client;
    }

    public function destroy(Client $client)
    {
        if ($client->projects()->exists()) {
            abort(422, 'This client has projects. Delete or move them first.');
        }
        $client->delete();

        return response()->noContent();
    }
}
