<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Project;
use App\Models\Setting;
use App\Support\Money;
use Illuminate\Support\Str;

class InvoiceService
{
    /** Items, payments and carried-forward balance that an invoice covers. */
    public static function collect(Project $p, ?string $period = null): array
    {
        $items = $p->items()->when($period, fn ($q) => $q->where('period', $period))->get()->map(fn ($i) => [
            'name' => $i->name, 'description' => $i->description, 'short_label' => $i->short_label,
            'unit_price' => $i->unit_price, 'quantity' => $i->quantity, 'billing' => $i->billing,
            'unit' => $i->unit, 'amount' => $i->amount, 'is_free' => $i->is_free, 'rate_text' => $i->rate_text,
        ])->all();

        $payments = $p->payments();
        $forward = 0.0;
        if ($period) {
            $start = $period.'-01';
            $billedBefore = (float) $p->items()->where('period', '<', $period)->sum('amount');
            $paidBefore = (float) $p->payments()->where('paid_at', '<', $start)->sum('amount');
            $forward = max(round($billedBefore - $paidBefore, 2), 0);
            $payments = $payments->where('paid_at', '>=', $start);
        }

        $payments = $payments->get()->map(fn ($x) => [
            'amount' => $x->amount, 'label' => $x->label, 'method' => $x->method, 'paid_at' => $x->paid_at->toDateString(),
        ])->all();

        return [$items, $payments, $forward];
    }

    public static function preview(Project $p, ?string $period = null): array
    {
        [$items, $payments, $forward] = self::collect($p, $period);
        $snap = InvoiceBuilder::build($items, $payments, ['forward' => $forward]);

        return [
            'snapshot' => $snap,
            'payment_terms' => $snap['has_pending'] ? 'Balance on delivery' : 'Due on receipt',
            'issue_date' => now()->toDateString(),
        ];
    }

    public static function create(Project $p, array $in): Invoice
    {
        [$items, $payments, $forward] = self::collect($p, $in['period'] ?? null);
        $opts = ['forward' => $forward];
        foreach (['note', 'payment_record'] as $key) {
            if (isset($in[$key])) {
                $opts[$key] = $in[$key];
            }
        }
        $snap = InvoiceBuilder::build($items, $payments, $opts);
        $issue = $in['issue_date'] ?? now()->toDateString();

        return Invoice::create([
            'project_id' => $p->id,
            'number' => Invoice::nextNumber($issue),
            'issue_date' => $issue,
            'payment_terms' => $in['payment_terms'] ?? ($snap['has_pending'] ? 'Balance on delivery' : 'Due on receipt'),
            'period' => $in['period'] ?? null,
            'snapshot' => $snap,
            'subtotal' => $snap['subtotal'], 'paid' => $snap['paid'], 'balance' => $snap['balance'],
            'token' => Str::random(40),
        ]);
    }

    public static function pdfData(Invoice $inv): array
    {
        $inv->loadMissing('project.client');

        return InvoiceBuilder::pdfData($inv->snapshot, [
            'invoice_no' => $inv->number,
            'issue_date' => $inv->issue_date->format('j M Y'),
            'payment_terms' => $inv->payment_terms,
            'client_name' => $inv->project->client->name,
            'client_desc' => (string) ($inv->project->description ?: $inv->project->title),
            'company' => Setting::get('company_name', 'Rivoxa'),
            'site' => Setting::get('company_site', ''),
        ]);
    }

    public static function pdf(Invoice $inv): string
    {
        return InvoicePdf::make()->invoice(self::pdfData($inv));
    }

    public static function shareMessage(Invoice $inv): string
    {
        $inv->loadMissing('project.client');
        $balance = $inv->balance > 0 ? ' Balance due: '.Money::rs($inv->balance).'.' : ' Fully paid, thank you!';

        return "Hello {$inv->project->client->name}, here is your invoice {$inv->number} from ".Setting::get('company_name', 'Rivoxa').'.'.$balance
            .' View / download: '.route('invoice.public', $inv->token);
    }
}
