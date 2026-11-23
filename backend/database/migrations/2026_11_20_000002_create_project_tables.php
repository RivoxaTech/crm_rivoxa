<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('projects', function (Blueprint $t) {
            $t->id();
            $t->foreignId('client_id')->constrained()->cascadeOnDelete();
            $t->string('title');
            $t->string('description')->nullable();        // shown under "Billed to" on invoices
            $t->string('type')->default('one_time');      // one_time | monthly
            $t->string('status')->default('active');      // active | closed
            $t->string('split_mode')->default('half');    // half | equal
            $t->date('start_date')->nullable();
            $t->timestamp('closed_at')->nullable();
            $t->decimal('final_revenue', 12, 2)->nullable();
            $t->decimal('final_expenses', 12, 2)->nullable();
            $t->decimal('final_profit', 12, 2)->nullable();
            $t->json('recurring')->nullable();            // monthly lines: name, description, unit_price, cost
            $t->boolean('auto_generate')->default(false);
            $t->unsignedTinyInteger('billing_day')->default(1);
            $t->string('last_billed_period', 7)->nullable();
            $t->text('notes')->nullable();
            $t->timestamps();
        });

        Schema::create('project_items', function (Blueprint $t) {
            $t->id();
            $t->foreignId('project_id')->constrained()->cascadeOnDelete();
            $t->string('kind')->default('other');
            $t->string('name');
            $t->string('description')->nullable();
            $t->string('short_label')->nullable();
            $t->string('rate_text')->nullable();
            $t->string('billing')->default('fixed');      // fixed | per_unit
            $t->string('unit')->nullable();
            $t->decimal('unit_price', 12, 2)->default(0);
            $t->decimal('quantity', 12, 2)->nullable();
            $t->decimal('amount', 12, 2)->nullable();     // null = decided on delivery
            $t->decimal('cost', 12, 2)->default(0);
            $t->boolean('is_free')->default(false);
            $t->boolean('is_addon')->default(false);
            $t->string('period', 7)->nullable();          // monthly projects: YYYY-MM
            $t->date('added_on')->nullable();
            $t->unsignedInteger('sort')->default(0);
            $t->timestamps();
        });

        Schema::create('payments', function (Blueprint $t) {
            $t->id();
            $t->foreignId('project_id')->constrained()->cascadeOnDelete();
            $t->decimal('amount', 12, 2);
            $t->string('method')->default('bank');
            $t->date('paid_at');
            $t->string('label')->nullable();
            $t->string('reference')->nullable();
            $t->string('receipt_path')->nullable();
            $t->string('note')->nullable();
            $t->timestamps();
        });

        Schema::create('expenses', function (Blueprint $t) {
            $t->id();
            $t->foreignId('project_id')->nullable()->constrained()->cascadeOnDelete(); // null = company expense
            $t->foreignId('project_item_id')->nullable()->constrained()->nullOnDelete();
            $t->string('title');
            $t->text('reason');
            $t->string('category')->nullable();
            $t->decimal('amount', 12, 2);
            $t->date('spent_on');
            $t->string('paid_by')->default('company');   // company | partner
            $t->foreignId('partner_id')->nullable()->constrained()->nullOnDelete();
            $t->date('reimbursed_on')->nullable();
            $t->timestamps();
        });

        Schema::create('invoices', function (Blueprint $t) {
            $t->id();
            $t->foreignId('project_id')->constrained()->cascadeOnDelete();
            $t->string('number')->unique();
            $t->date('issue_date');
            $t->string('payment_terms');
            $t->string('period', 7)->nullable();
            $t->json('snapshot');
            $t->decimal('subtotal', 12, 2);
            $t->decimal('paid', 12, 2);
            $t->decimal('balance', 12, 2);
            $t->string('token', 40)->unique();
            $t->timestamp('whatsapp_at')->nullable();
            $t->timestamp('emailed_at')->nullable();
            $t->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['invoices', 'expenses', 'payments', 'project_items', 'projects'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
