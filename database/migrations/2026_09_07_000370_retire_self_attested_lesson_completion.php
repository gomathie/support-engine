<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Stop treating a learner's own tick as evidence of anything.
 *
 * Every one of the seeded lessons was `acknowledge` — the trainee asserted they
 * had learned the material and the platform reported that assertion as progress.
 * That is the model this whole programme exists to leave behind.
 *
 * They become `view`, which records honestly that the material was opened and
 * claims nothing more. The claim of competence now comes from the final exam and
 * the practical task, neither of which the learner marks.
 *
 * `acknowledge` survives as an option for genuine attestations — "I have read the
 * data protection policy" is a record worth having and only the person can make
 * it — but it is no longer the default, and it is no longer used by any training
 * lesson.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lessons', function (Blueprint $table) {
            $table->string('completion_requirement', 20)->default('view')->change();
        });

        DB::table('lessons')
            ->where('completion_requirement', 'acknowledge')
            ->update(['completion_requirement' => 'view', 'updated_at' => now()]);
    }

    public function down(): void
    {
        Schema::table('lessons', function (Blueprint $table) {
            $table->string('completion_requirement', 20)->default('acknowledge')->change();
        });

        // Deliberately not reversed. Which lessons were self-attested before
        // this ran is not recorded anywhere, and guessing would put the wrong
        // ones back.
    }
};
