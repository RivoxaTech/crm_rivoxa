<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PaymentController extends Controller
{
    private function rules(): array
    {
        return [
            'amount' => 'required|numeric|gt:0', 'method' => 'required|in:bank,cash,easypaisa,jazzcash,cheque,other',
            'paid_at' => 'required|date', 'label' => 'nullable|string|max:80', 'reference' => 'nullable|string|max:120',
            'note' => 'nullable|string|max:255', 'receipt' => 'nullable|file|mimes:jpg,jpeg,png,pdf,webp|max:5120',
        ];
    }

    private function open(Project $project): void
    {
        abort_if($project->isClosed(), 422, 'This project is closed. Reopen it to make changes.');
    }

    public function store(Request $request, Project $project)
    {
        $this->open($project);
        $data = $request->validate($this->rules());
        $payment = $project->payments()->create(collect($data)->except('receipt')->all());
        $this->storeReceipt($request, $payment);

        return $payment;
    }

    public function update(Request $request, Payment $payment)
    {
        $this->open($payment->project);
        $payment->update(collect($request->validate($this->rules()))->except('receipt')->all());
        $this->storeReceipt($request, $payment);

        return $payment->fresh();
    }

    public function destroy(Payment $payment)
    {
        $this->open($payment->project);
        if ($payment->receipt_path) {
            Storage::delete($payment->receipt_path);
        }
        $payment->delete();

        return response()->noContent();
    }

    public function receipt(Payment $payment)
    {
        abort_unless($payment->receipt_path && Storage::exists($payment->receipt_path), 404);

        return Storage::response($payment->receipt_path);
    }

    private function storeReceipt(Request $request, Payment $payment): void
    {
        if ($request->hasFile('receipt')) {
            if ($payment->receipt_path) {
                Storage::delete($payment->receipt_path);
            }
            $payment->update(['receipt_path' => $request->file('receipt')->store('receipts')]);
        }
    }
}
