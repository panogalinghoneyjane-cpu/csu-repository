<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Physical copy indicator: tells students that a printed copy of the resource
 * is also available inside the library, with its call number and location.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('resources', function (Blueprint $table) {
            $table->boolean('has_physical_copy')->default(false)->after('status');
            $table->string('call_number', 50)->nullable()->after('has_physical_copy');     // e.g. QA76.76 .S46 2023
            $table->string('shelf_location', 100)->nullable()->after('call_number');       // e.g. CCIS Section, 2nd Floor
        });
    }

    public function down(): void
    {
        Schema::table('resources', function (Blueprint $table) {
            $table->dropColumn(['has_physical_copy', 'call_number', 'shelf_location']);
        });
    }
};