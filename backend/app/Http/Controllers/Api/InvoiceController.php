<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\InvoiceMail;
use App\Models\Invoice;
use App\Models\Project;
use App\Models\Setting;
use App\Services\InvoiceService;
use App\Support\Money;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class InvoiceController extends Controller
{
    public function index(Request $request)
    {
        return Invoice::with('project:id,title,client_id', 'project.client:id,name')
            ->when($request->project_id, fn ($q, $id) => $q->where('project_id', $id))
            ->when($request->q, fn ($q, $s) => $q->where(fn ($w) => $w->where('number', 'like', "%$s%")->orWhereHas('project.client', fn ($c) => $c->where('name', 'like', "%$s%"))))
            ->latest('id')->get();
    }

    /** Defaults for the "generate invoice" dialog. */
    public function preview(Request $request, Project $project)
    {
        return InvoiceService::preview($project, $request->query('period'));
    }

    public function store(Request $request, Project $project)
    {
        $data = $request->validate([
            'issue_date' => 'required|date', 'payment_terms' => 'required|string|max:80', 'period' => 'nullable|date_format:Y-m',
            'note' => 'nullable|string|max:600', 'payment_record' => 'nullable|string|max:600',
        ]);

        // empty strings arrive as null; an emptied box means "show nothing", not "use the default"
        foreach (['note', 'payment_record'] as $key) {
            if ($request->has($key)) {
                $data[$key] = (string) ($data[$key] ?? '');
            }
        }

        return InvoiceService::create($project, $data);
    }

    public function destroy(Invoice $invoice)
    {
        $invoice->delete();

        return response()->noContent();
    }

    public function pdf(Request $request, Invoice $invoice)
    {
        $disposition = $request->boolean('download') ? 'attachment' : 'inline';

        return response(InvoiceService::pdf($invoice), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => $disposition.'; filename="'.$invoice->number.'.pdf"',
        ]);
    }

    /** Everything the "send" buttons need. */
    public function share(Invoice $invoice)
    {
        $client = $invoice->load('project.client')->project->client;
        $phone = Money::phone($client->whatsapp ?: $client->phone, Setting::get('country_code', '92'));
        $message = InvoiceService::shareMessage($invoice);

        return [
            'url' => route('invoice.public', $invoice->token),
            'message' => $message,
            'phone' => $phone,
            'whatsapp_url' => 'https://wa.me/'.($phone ?? '').'?text='.rawurlencode($message),
            'email' => $client->email,
            'client' => $client->name,
        ];
    }

    public function whatsappSent(Invoice $invoice)
    {
        $invoice->update(['whatsapp_at' => now()]);

        return $invoice;
    }

    public function email(Request $request, Invoice $invoice)
    {
        $data = $request->validate(['to' => 'required|email', 'message' => 'nullable|string|max:2000']);
        $invoice->load('project.client');
        $company = Setting::get('company_name', 'Rivoxa');
        $body = $data['message'] ?: "Hello {$invoice->project->client->name},\n\nPlease find invoice {$invoice->number} attached.\n\nThank you for choosing $company.";

        try {
            Mail::to($data['to'])->send(new InvoiceMail($invoice, $body, InvoiceService::pdf($invoice)));
        } catch (\Throwable $e) {
            report($e);
            abort(422, 'The email could not be sent. Check the mail settings in .env. ('.$e->getMessage().')');
        }
        $invoice->update(['emailed_at' => now()]);

        return $invoice;
    }
}
