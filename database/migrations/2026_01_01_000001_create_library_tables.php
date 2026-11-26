<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Faculty RECOMMEND resources (they do not upload files).
        Schema::create('recommendations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('faculty_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('authors');
            $table->string('publisher')->nullable();
            $table->string('edition')->nullable();
            $table->unsignedSmallInteger('publication_year')->nullable();
            $table->string('resource_type');
            $table->string('source_link')->nullable();   // where the librarian can find/acquire it
            $table->text('reason');                      // why the faculty recommends it
            // pending -> approved -> fulfilled (uploaded)  |  rejected  |  needs_revision -> pending
            $table->enum('status', ['pending', 'approved', 'rejected', 'needs_revision', 'fulfilled'])->default('pending');
            $table->text('librarian_remarks')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
        });

        // Approved digital resources stored in the repository (uploaded by the librarian only).
        Schema::create('resources', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subject_id')->constrained()->cascadeOnDelete();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('recommendation_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->string('authors');
            $table->unsignedSmallInteger('publication_year');
            $table->string('resource_type');             // Ebook, Journal, Research Paper, Other
            $table->string('academic_year')->nullable(); // e.g. 2026-2027
            $table->text('description')->nullable();
            $table->string('keywords')->nullable();      // comma-separated, used by search + related resources
            $table->string('file_path');                 // private storage path (never public)
            $table->unsignedInteger('page_count')->nullable();
            $table->enum('status', ['active', 'archived'])->default('active');
            $table->timestamps();
        });

        // Every time a user opens a resource in the reader.
        Schema::create('access_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('resource_id')->constrained()->cascadeOnDelete();
            $table->timestamp('accessed_at')->useCurrent();
        });

        // Every search, so librarians can see demand and searches that found nothing.
        Schema::create('search_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('keyword');
            $table->unsignedInteger('results_count')->default(0);
            $table->timestamp('searched_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('search_logs');
        Schema::dropIfExists('access_logs');
        Schema::dropIfExists('resources');
        Schema::dropIfExists('recommendations');
    }
};
