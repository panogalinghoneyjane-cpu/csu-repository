<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Keep a copy of what the faculty member submitted each time (title, link, reason, ...),
 * so earlier versions are never lost when a recommendation is revised and resubmitted.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('recommendation_histories', function (Blueprint $table) {
            $table->json('details')->nullable()->after('remarks');
        });

        // Existing recommendations: save their current details on their latest submission step
        $fields = ['title', 'authors', 'publisher', 'edition', 'publication_year', 'resource_type', 'source_link', 'reason'];
        foreach (DB::table('recommendations')->get() as $r) {
            $step = DB::table('recommendation_histories')
                ->where('recommendation_id', $r->id)->whereIn('action', ['submitted', 'resubmitted'])
                ->orderByDesc('created_at')->orderByDesc('id')->first();
            if ($step) {
                DB::table('recommendation_histories')->where('id', $step->id)
                    ->update(['details' => json_encode(collect((array) $r)->only($fields))]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('recommendation_histories', function (Blueprint $table) {
            $table->dropColumn('details');
        });
    }
};
