<?php

namespace App\Console\Commands;

use App\Mail\InvoiceMail;
use App\Models\Project;
use App\Models\Setting;
use App\Services\InvoiceService;
use App\Services\RecurringBilling;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class BillRecurringProjects extends Command
{
    protected $signature = 'invoices:recurring';
    protected $description = 'Create this month\'s invoice for monthly projects that have auto-generate turned on';

    public function handle(): int
    {
        $period = now()->format('Y-m');

        Project::with('client')->where('type', 'monthly')->where('status', 'active')->where('auto_generate', true)
            ->where('billing_day', '<=', now()->day)
            ->where(fn ($q) => $q->whereNull('last_billed_period')->orWhere('last_billed_period', '<', $period))
            ->each(function (Project $project) use ($period) {
                try {
                    $invoice = RecurringBilling::generate($project, $period);
                    $this->info("Billed {$project->title}: {$invoice->number}");

                    if (Setting::get('auto_email_recurring') === '1' && $project->client->email) {
                        $body = "Hello {$project->client->name},\n\nPlease find invoice {$invoice->number} attached.\n\nThank you.";
                        Mail::to($project->client->email)->send(new InvoiceMail($invoice, $body, InvoiceService::pdf($invoice)));
                        $invoice->update(['emailed_at' => now()]);
                    }
                } catch (\Throwable $e) {
                    $this->error("{$project->title}: {$e->getMessage()}");
                }
            });

        return self::SUCCESS;
    }
}
