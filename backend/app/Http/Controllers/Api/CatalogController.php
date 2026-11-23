<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CatalogService;
use Illuminate\Http\Request;

class CatalogController extends Controller
{
    private function rules(): array
    {
        return [
            'name' => 'required|string|max:160', 'description' => 'nullable|string|max:300',
            'billing' => 'required|in:fixed,per_unit',
            'unit' => 'nullable|string|max:30', 'unit_price' => 'nullable|numeric|min:0', 'cost' => 'nullable|numeric|min:0',
            'rate_text' => 'nullable|string|max:60', 'short_label' => 'nullable|string|max:80',
        ];
    }

    public function index()
    {
        return CatalogService::orderBy('name')->get();
    }

    public function store(Request $request)
    {
        $data = $request->validate($this->rules());

        return CatalogService::create($data + ['kind' => $data['name']]);
    }

    public function update(Request $request, CatalogService $catalog)
    {
        $data = $request->validate($this->rules());
        $catalog->update($data + ['kind' => $data['name']]);

        return $catalog;
    }

    public function destroy(CatalogService $catalog)
    {
        $catalog->delete();

        return response()->noContent();
    }
}
