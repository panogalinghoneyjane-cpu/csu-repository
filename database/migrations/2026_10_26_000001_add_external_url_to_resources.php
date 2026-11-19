<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Lets the librarian add a resource as EITHER an uploaded PDF (file_path)
 * OR an online link (external_url), e.g. an open-access journal or publisher e-book page.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('resources', function (Blueprint $table) {
            $table->string('external_url', 500)->nullable();
            $table->string('file_path')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('resources', function (Blueprint $table) {
            $table->dropColumn('external_url');
        });
    }
};