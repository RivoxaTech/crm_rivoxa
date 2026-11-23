<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class SetAdmin extends Command
{
    protected $signature = 'rivoxa:admin {email} {password}';
    protected $description = 'Create the admin login, or reset its password';

    public function handle(): int
    {
        User::updateOrCreate(['email' => $this->argument('email')], ['name' => 'Admin', 'password' => $this->argument('password')]);
        $this->info('Admin login saved.');

        return self::SUCCESS;
    }
}
