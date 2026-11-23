<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use PDO;
use Throwable;

class Install extends Command
{
    protected $signature = 'rivoxa:install';
    protected $description = 'Create the database if missing, run migrations and seed the admin login';

    public function handle(): int
    {
        $cfg = config('database.connections.mysql');
        $name = $cfg['database'];

        try {
            // connect without a database name so we can create it
            $pdo = new PDO("mysql:host={$cfg['host']};port={$cfg['port']}", $cfg['username'], $cfg['password'] ?? '');
            $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$name}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            $this->info("Database `{$name}` is ready.");
        } catch (Throwable $e) {
            $this->error('Cannot connect to MySQL: '.$e->getMessage());
            $this->line('Check DB_HOST, DB_USERNAME and DB_PASSWORD in backend/.env and that MySQL is running.');

            return self::FAILURE;
        }

        DB::purge();
        $this->call('migrate', ['--force' => true, '--seed' => true]);

        $this->newLine();
        $this->info('Done. Start the API with: php artisan serve');

        return self::SUCCESS;
    }
}
