<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $t) {
            $t->string('key')->primary();
            $t->text('value')->nullable();
        });

        Schema::create('partners', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('phone')->nullable();
            $t->string('email')->nullable();
            $t->timestamps();
        });

        Schema::create('clients', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('contact_person')->nullable();
            $t->string('phone')->nullable();
            $t->string('whatsapp')->nullable();
            $t->string('email')->nullable();
            $t->string('address')->nullable();
            $t->text('notes')->nullable();
            $t->timestamps();
        });

        Schema::create('catalog_services', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('description')->nullable();
            $t->string('kind')->default('other');
            $t->string('billing')->default('fixed');
            $t->string('unit')->nullable();
            $t->decimal('unit_price', 12, 2)->default(0);
            $t->decimal('cost', 12, 2)->default(0);
            $t->string('rate_text')->nullable();
            $t->string('short_label')->nullable();
            $t->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['catalog_services', 'clients', 'partners', 'settings'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
