<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/** Service prices and costs are optional: they are decided per client when a project is quoted. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('catalog_services', function (Blueprint $t) {
            $t->decimal('unit_price', 12, 2)->nullable()->default(null)->change();
            $t->decimal('cost', 12, 2)->nullable()->default(null)->change();
        });

        // a service's type is the service itself (Web Development, Mobile App Development, ...)
        DB::table('catalog_services')->update(['kind' => DB::raw('name')]);
    }

    public function down(): void
    {
        Schema::table('catalog_services', function (Blueprint $t) {
            $t->decimal('unit_price', 12, 2)->default(0)->nullable(false)->change();
            $t->decimal('cost', 12, 2)->default(0)->nullable(false)->change();
        });
    }
};
