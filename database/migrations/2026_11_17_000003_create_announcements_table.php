<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Announcements posted by the librarian (e.g. book fair schedule). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('announcements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();   // librarian who posted it
            $table->string('title');
            $table->text('body');
            $table->enum('audience', ['all', 'faculty', 'student'])->default('all');
            $table->date('event_date')->nullable();     // e.g. the day of the book fair
            $table->date('show_until')->nullable();     // hidden after this date (blank = always shown)
            $table->boolean('is_pinned')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('announcements');
    }
};
