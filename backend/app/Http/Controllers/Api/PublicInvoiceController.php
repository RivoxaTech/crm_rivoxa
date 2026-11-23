<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Services\InvoiceService;

class PublicInvoiceController extends Controller
{
    /** Link shared with clients; the long random token is the only key. */
    public function show(string $token)
    {
        $invoice = Invoice::where('token', $token)->firstOrFail();

        return response(InvoiceService::pdf($invoice), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$invoice->number.'.pdf"',
        ]);
    }
}
