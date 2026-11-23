<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_shares', function (Blueprint $t) {
            $t->id();
            $t->foreignId('project_id')->constrained()->cascadeOnDelete();
            $t->foreignId('partner_id')->nullable()->constrained()->nullOnDelete();
            $t->string('party');                          // partner | company
            $t->decimal('percent', 5, 2);
            $t->decimal('amount', 12, 2);
            $t->decimal('paid_amount', 12, 2)->default(0);
            $t->timestamps();
        });

        Schema::create('payouts', function (Blueprint $t) {
            $t->id();
            $t->foreignId('project_share_id')->constrained()->cascadeOnDelete();
            $t->foreignId('partner_id')->constrained()->cascadeOnDelete();
            $t->decimal('amount', 12, 2);
            $t->date('paid_on');
            $t->string('method')->default('bank');
            $t->string('note')->nullable();
            $t->timestamps();
        });

        Schema::create('account_adjustments', function (Blueprint $t) {
            $t->id();
            $t->string('type');                           // deposit | withdrawal
            $t->decimal('amount', 12, 2);
            $t->date('adjusted_on');
            $t->string('note');
            $t->timestamps();
        });

        Schema::create('client_services', function (Blueprint $t) {
            $t->id();
            $t->foreignId('client_id')->constrained()->cascadeOnDelete();
            $t->foreignId('project_id')->nullable()->constrained()->nullOnDelete();
            $t->string('kind')->default('domain');        // domain | hosting | other
            $t->string('name');
            $t->string('provider')->nullable();
            $t->date('purchased_on')->nullable();
            $t->date('expires_on')->nullable();
            $t->decimal('renewal_price', 12, 2)->nullable();
            $t->decimal('renewal_cost', 12, 2)->nullable();
            $t->text('notes')->nullable();
            $t->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['client_services', 'account_adjustments', 'payouts', 'project_shares'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
