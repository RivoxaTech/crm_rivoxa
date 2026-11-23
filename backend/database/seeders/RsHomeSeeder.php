<?php

namespace Database\Seeders;

use App\Models\Client;
use App\Models\ClientService;
use App\Models\Expense;
use App\Models\Partner;
use App\Models\Project;
use App\Services\InvoiceService;
use App\Services\ItemWriter;
use Illuminate\Database\Seeder;

/** The RS Home project (matches invoices RVX-2026-001..003). Runs with db:seed, only once. */
class RsHomeSeeder extends Seeder
{
    public function run(): void
    {
        if (Client::where('name', 'RS Home')->exists()) {
            return;
        }

        $partner = Partner::orderBy('id')->first();

        $client = Client::create(['name' => 'RS Home', 'contact_person' => 'Demo contact', 'phone' => '0300 1234567', 'email' => 'demo@example.com']);
        $project = Project::create([
            'client_id' => $client->id, 'title' => 'RS Home website', 'description' => 'Website development & hosting services',
            'type' => 'one_time', 'split_mode' => 'half', 'start_date' => '2026-09-26',
        ]);

        $fixed = fn ($name, $desc, $kind, $price, $cost, $rate = null) => ItemWriter::save($project, [
            'name' => $name, 'description' => $desc, 'kind' => $kind, 'billing' => 'fixed', 'unit_price' => $price,
            'cost' => $cost, 'rate_text' => $rate, 'added_on' => '2026-09-26',
        ]);
        $fixed('Website Development', 'Design, development & deployment', 'website', 35000, 0);
        $fixed('Domain Registration', '3-year registration', 'domain', 15700, 12000, '15,700 / 3 yrs');
        $fixed('Server & Hosting', 'Annual server and hosting plan (1 year)', 'hosting', 10000, 6000, '10,000 / yr');
        $images = ItemWriter::save($project, [
            'name' => 'Image Resizing & Logo Changes', 'description' => 'Per-image resizing plus logo changes; total calculated on delivery',
            'short_label' => 'Image / logo changes', 'kind' => 'images', 'billing' => 'per_unit', 'unit' => 'image', 'unit_price' => 30, 'rate_text' => '30 / image', 'added_on' => '2026-09-26',
        ]);

        $project->payments()->create(['amount' => 5000, 'method' => 'bank', 'paid_at' => '2026-09-26', 'label' => 'Advance paid']);
        InvoiceService::create($project, ['issue_date' => '2026-09-26']);

        $project->payments()->create(['amount' => 25700, 'method' => 'bank', 'paid_at' => '2026-10-03', 'label' => '2nd payment received']);
        InvoiceService::create($project, ['issue_date' => '2026-10-03']);

        // delivery: images charged Rs 12,400 in total, plus the All Access package
        ItemWriter::save($project, [
            'name' => $images->name, 'description' => 'Image resizing and logo changes, as delivered', 'short_label' => $images->short_label,
            'kind' => 'images', 'billing' => 'per_unit', 'unit' => 'image', 'unit_price' => 30, 'rate_text' => '30 / image', 'amount' => 12400,
        ], $images);
        ItemWriter::save($project, ['name' => 'All Access', 'description' => 'All-access package', 'kind' => 'addon', 'billing' => 'fixed', 'unit_price' => 13600, 'is_addon' => true, 'added_on' => '2026-11-10']);
        $project->payments()->create(['amount' => 25000, 'method' => 'cash', 'paid_at' => '2026-11-19', 'label' => 'Cash received (19 Nov)']);
        InvoiceService::create($project, [
            'issue_date' => '2026-11-20',
            'payment_record' => 'Rs 5,000 and Rs 25,700 paid via online bank transfer; bank receipts / screenshots are the source of truth. Rs 25,000 paid in cash on 19 Nov 2026.',
        ]);

        Expense::create([
            'project_id' => $project->id, 'title' => 'Fuel for client visits', 'reason' => 'Two visits to the client store',
            'amount' => 1500, 'spent_on' => '2026-10-12', 'paid_by' => 'partner', 'partner_id' => $partner->id,
        ]);
        Expense::create(['title' => 'Facebook ads', 'reason' => 'Rivoxa page promotion', 'category' => 'ads', 'amount' => 3000, 'spent_on' => '2026-11-05', 'paid_by' => 'company']);

        ClientService::create(['client_id' => $client->id, 'project_id' => $project->id, 'kind' => 'domain', 'name' => 'rshome.example', 'provider' => 'Registrar', 'purchased_on' => '2026-09-26', 'expires_on' => '2029-09-26', 'renewal_price' => 5500, 'renewal_cost' => 4000]);
        ClientService::create(['client_id' => $client->id, 'project_id' => $project->id, 'kind' => 'hosting', 'name' => 'Shared hosting', 'provider' => 'cPanel host', 'purchased_on' => '2026-09-26', 'expires_on' => '2026-12-05', 'renewal_price' => 10000, 'renewal_cost' => 6000]);
    }
}
