<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Expense;
use App\Models\Partner;
use App\Models\Payout;
use App\Models\ProjectShare;
use App\Models\Setting;
use App\Services\InvoicePdf;
use App\Support\Money;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PartnerController extends Controller
{
    private function totals(Partner $p): array
    {
        $earned = (float) ProjectShare::where('partner_id', $p->id)->sum('amount');
        $paid = (float) ProjectShare::where('partner_id', $p->id)->sum('paid_amount');

        return [
            'earned' => round($earned, 2), 'paid' => round($paid, 2), 'unpaid' => round($earned - $paid, 2),
            'owed_expenses' => round((float) Expense::where('partner_id', $p->id)->where('paid_by', 'partner')->whereNull('reimbursed_on')->sum('amount'), 2),
        ];
    }

    public function index()
    {
        return Partner::orderBy('id')->get()->map(fn ($p) => $p->toArray() + $this->totals($p));
    }

    public function show(Partner $partner)
    {
        return [
            'partner' => $partner->toArray() + $this->totals($partner),
            'shares' => ProjectShare::with('project:id,title,closed_at,client_id', 'project.client:id,name')
                ->where('partner_id', $partner->id)->latest('id')->get()->map(fn ($s) => $s->toArray() + ['remaining' => $s->remaining()]),
            'payouts' => Payout::with('share.project:id,title')->where('partner_id', $partner->id)->latest('paid_on')->latest('id')->get(),
            'owed_expenses' => Expense::with('project:id,title')->where('partner_id', $partner->id)->where('paid_by', 'partner')->whereNull('reimbursed_on')->get(),
        ];
    }

    public function update(Request $request, Partner $partner)
    {
        $partner->update($request->validate(['name' => 'required|string|max:80', 'phone' => 'nullable|string|max:40', 'email' => 'nullable|email|max:160']));

        return $partner;
    }

    private function payoutRules(): array
    {
        return ['amount' => 'required|numeric|gt:0', 'paid_on' => 'required|date', 'method' => 'required|in:bank,cash,easypaisa,jazzcash,cheque,other', 'note' => 'nullable|string|max:255'];
    }

    public function payout(Request $request, ProjectShare $share)
    {
        abort_unless($share->party === 'partner', 422, 'The company share stays in the company account.');
        $data = $request->validate($this->payoutRules());
        abort_if($data['amount'] > $share->remaining() + 0.001, 422, 'Amount is more than the unpaid share ('.Money::rs($share->remaining()).').');

        return DB::transaction(function () use ($share, $data) {
            $payout = $share->payouts()->create($data + ['partner_id' => $share->partner_id]);
            $share->increment('paid_amount', $data['amount']);

            return $payout;
        });
    }

    /** Pay across all unpaid shares of a partner, oldest first. */
    public function payAll(Request $request, Partner $partner)
    {
        $data = $request->validate($this->payoutRules());
        $shares = ProjectShare::where('partner_id', $partner->id)->whereColumn('paid_amount', '<', 'amount')->orderBy('id')->get();
        $unpaid = round((float) $shares->sum(fn ($s) => $s->remaining()), 2);
        abort_if($data['amount'] > $unpaid + 0.001, 422, 'Amount is more than the total unpaid ('.Money::rs($unpaid).').');

        DB::transaction(function () use ($shares, $data, $partner) {
            $left = (float) $data['amount'];
            foreach ($shares as $share) {
                if ($left <= 0) {
                    break;
                }
                $part = round(min($left, $share->remaining()), 2);
                $share->payouts()->create(['amount' => $part] + $data + ['partner_id' => $partner->id]);
                $share->increment('paid_amount', $part);
                $left = round($left - $part, 2);
            }
        });

        return $this->show($partner);
    }

    public function destroyPayout(Payout $payout)
    {
        DB::transaction(function () use ($payout) {
            $payout->share->decrement('paid_amount', $payout->amount);
            $payout->delete();
        });

        return response()->noContent();
    }

    public function voucher(Payout $payout)
    {
        $payout->load('partner', 'share.project.client');
        $pdf = InvoicePdf::make()->voucher([
            'voucher_no' => 'PV-'.$payout->paid_on->format('Y').'-'.str_pad((string) $payout->id, 3, '0', STR_PAD_LEFT),
            'date' => $payout->paid_on->format('j M Y'),
            'partner' => $payout->partner->name,
            'project' => $payout->share->project->title.' — '.$payout->share->project->client->name,
            'amount' => Money::rs($payout->amount),
            'method' => ucfirst($payout->method),
            'note' => (string) $payout->note,
            'company' => Setting::get('company_name', 'Rivoxa'),
            'site' => Setting::get('company_site', ''),
            'title' => 'PAYOUT',
        ]);

        return response($pdf, 200, ['Content-Type' => 'application/pdf', 'Content-Disposition' => 'inline; filename="payout-'.$payout->id.'.pdf"']);
    }
}
