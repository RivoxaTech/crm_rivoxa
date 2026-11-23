<?php

namespace Database\Seeders;

use App\Models\CatalogService;
use App\Models\Partner;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $email = config('rivoxa.admin_email');
        if (! User::where('email', $email)->exists()) {
            $password = config('rivoxa.admin_password') ?: Str::random(12);
            User::create(['name' => 'Admin', 'email' => $email, 'password' => $password]);
            $this->command?->info("Admin login created: $email / $password");
        }

        // the two partners (renames the old placeholder names too)
        foreach ([1 => 'Usman', 2 => 'Mahrose'] as $n => $name) {
            $partner = Partner::where('name', "Partner $n")->orWhere('name', $name)->first();
            $partner ? $partner->update(['name' => $name]) : Partner::create(['name' => $name]);
        }

        // Everything Rivoxa offers (rivoxa.tech). Prices stay empty: they are set per client when quoting.
        foreach ([
            ['Website Development', 'Fast, scalable websites and web platforms built for growth.', 'fixed', null],
            ['Mobile App Development', 'Native and cross-platform apps for iOS and Android.', 'fixed', null],
            ['SaaS Product Development', 'End-to-end SaaS platforms, including architecture, billing and infrastructure.', 'fixed', null],
            ['Custom Software Development', 'Bespoke systems designed around the client\'s operations.', 'fixed', null],
            ['AI Automation Solutions', 'Workflows that reduce manual work and help operations scale.', 'fixed', null],
            ['AI Chatbot Development', 'Conversational AI that handles context and resolves customer requests.', 'fixed', null],
            ['CMS & Admin Panel Development', 'Internal tools and dashboards for operational control.', 'fixed', null],
            ['Domain Registration', 'Domain name registration', 'fixed', null],
            ['Server & Hosting', 'Server and hosting plan', 'fixed', null],
            ['Ads Management', 'Monthly advertising management', 'fixed', null],
            ['Image Resizing & Logo Changes', 'Per-image enhancement, charged on delivery', 'per_unit', 'image'],
        ] as [$name, $desc, $billing, $unit]) {
            CatalogService::firstOrCreate(['name' => $name], ['description' => $desc, 'kind' => $name, 'billing' => $billing, 'unit' => $unit]);
        }

        // the RS Home project, added once
        $this->call(RsHomeSeeder::class);
    }
}
