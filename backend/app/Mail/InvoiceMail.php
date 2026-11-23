<?php

namespace App\Mail;

use App\Models\Invoice;
use App\Models\Setting;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class InvoiceMail extends Mailable
{
    public function __construct(public Invoice $invoice, public string $body, public string $pdf) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Invoice '.$this->invoice->number.' from '.Setting::get('company_name', 'Rivoxa'));
    }

    public function content(): Content
    {
        return new Content(view: 'emails.invoice');
    }

    public function attachments(): array
    {
        return [Attachment::fromData(fn () => $this->pdf, $this->invoice->number.'.pdf')->withMime('application/pdf')];
    }
}
