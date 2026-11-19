<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Recommendation history: one row for every step of a recommendation
 * (submitted, approved, rejected, returned for revision, resubmitted, added to repository),
 * with the date and time and who did it.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recommendation_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('recommendation_id')->constrained()->cascadeOnDelete();
            $table->string('action', 30);              // submitted, approved, rejected, needs_revision, resubmitted, fulfilled
            $table->text('remarks')->nullable();       // librarian's remarks at that time
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();   // who did it
            $table->timestamps();
        });

        // Fill in the history of recommendations that already exist (as far as the old data allows)
        foreach (DB::table('recommendations')->orderBy('id')->get() as $r) {
            $rows = [[
                'action' => 'submitted', 'remarks' => null, 'user_id' => $r->faculty_id, 'created_at' => $r->created_at,
            ]];

            if ($r->reviewed_at) {
                $decision = match ($r->status) {
                    'fulfilled' => 'approved',
                    'pending' => 'needs_revision',        // it was returned, then resubmitted
                    default => $r->status,
                };
                $rows[] = ['action' => $decision, 'remarks' => $r->librarian_remarks, 'user_id' => $r->reviewed_by, 'created_at' => $r->reviewed_at];

                if ($r->status === 'pending') {
                    $rows[] = ['action' => 'resubmitted', 'remarks' => null, 'user_id' => $r->faculty_id, 'created_at' => $r->updated_at];
                }
                if ($r->status === 'fulfilled') {
                    $addedAt = DB::table('resources')->where('recommendation_id', $r->id)->value('created_at') ?? $r->updated_at;
                    $rows[] = ['action' => 'fulfilled', 'remarks' => null, 'user_id' => $r->reviewed_by, 'created_at' => $addedAt];
                }
            }

            foreach ($rows as $row) {
                DB::table('recommendation_histories')->insert($row + [
                    'recommendation_id' => $r->id, 'updated_at' => $row['created_at'],
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('recommendation_histories');
    }
};
