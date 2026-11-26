<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Faculty can archive a class (hide it without deleting) and restore it later.
 * Skips the column when it already exists (it was first added directly in the database).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('course_classes', 'archived_at')) {
            Schema::table('course_classes', function (Blueprint $table) {
                $table->timestamp('archived_at')->nullable();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('course_classes', 'archived_at')) {
            Schema::table('course_classes', function (Blueprint $table) {
                $table->dropColumn('archived_at');
            });
        }
    }
};
