<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Library rule: every subject needs at least 5 resources.
 * A subject with fewer than 5 is reported as "Insufficient".
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('subjects')->update(['min_resources' => 5]);

        Schema::table('subjects', function (Blueprint $table) {
            $table->unsignedInteger('min_resources')->default(5)->change();
        });
    }

    public function down(): void
    {
        DB::table('subjects')->update(['min_resources' => 3]);

        Schema::table('subjects', function (Blueprint $table) {
            $table->unsignedInteger('min_resources')->default(3)->change();
        });
    }
};