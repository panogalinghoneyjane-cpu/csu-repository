<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * My Classes: a faculty member creates a class for a subject they handle, adds repository
 * resources to it, and shares the class link/code. Students save the class to their account.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('course_classes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('faculty_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained()->cascadeOnDelete();
            $table->string('section');                       // e.g. PRS1, BSIT 2-A
            $table->string('school_year', 20)->nullable();   // e.g. 2026-2027
            $table->string('semester', 30)->nullable();      // 1st Semester, 2nd Semester, Summer
            $table->text('description')->nullable();
            $table->string('code', 12)->unique();            // share code used in the class link
            $table->boolean('is_open')->default(true);       // closed = students can no longer join
            $table->timestamps();
        });

        // Resources the faculty member added to the class
        Schema::create('course_class_resource', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_class_id')->constrained()->cascadeOnDelete();
            $table->foreignId('resource_id')->constrained()->cascadeOnDelete();
            $table->string('note', 500)->nullable();         // e.g. "Read before the midterm"
            $table->timestamps();
            $table->unique(['course_class_id', 'resource_id']);
        });

        // Students who saved the class to their My Classes
        Schema::create('course_class_student', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_class_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['course_class_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('course_class_student');
        Schema::dropIfExists('course_class_resource');
        Schema::dropIfExists('course_classes');
    }
};
