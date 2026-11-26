<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Resource types are now only: Ebook, Journal, Research Paper, Other.
 * Existing Thesis records become Research Paper, and Module records become Other.
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (['resources', 'recommendations'] as $table) {
            DB::table($table)->where('resource_type', 'Thesis')->update(['resource_type' => 'Research Paper']);
            DB::table($table)->where('resource_type', 'Module')->update(['resource_type' => 'Other']);
        }
    }

    public function down(): void
    {
        // The old type of each record is not kept, so this change cannot be undone automatically.
    }
};
