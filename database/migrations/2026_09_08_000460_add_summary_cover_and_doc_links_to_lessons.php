<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The rest of what a lesson needs: a summary, a cover image, and links to the
 * documentation it was written from.
 *
 * `doc_links` is a list of {title, url} rather than a single reference string.
 * A lesson is usually drawn from more than one page — "Setting up DVRs" needs
 * the DVR page, the Video/CMS overview and the supported-devices list — and a
 * trainee should be able to follow each one rather than be told a chapter name
 * and left to search.
 *
 * The cover image goes on the `public` disk, following `courses.thumbnail_path`.
 * It is decoration on a card, not evidence: nothing about it is confidential,
 * and putting it behind a policy-checked route would mean a request per tile.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lessons', function (Blueprint $table) {
            // One or two sentences, shown on the lesson card and above the body.
            $table->string('summary', 500)->nullable()->after('description');

            $table->string('cover_image_path')->nullable()->after('summary');

            // [{title, url}, …] — shown after the lesson text, as source
            // material rather than as part of the teaching.
            $table->jsonb('doc_links')->nullable()->after('cover_image_path');
        });
    }

    public function down(): void
    {
        Schema::table('lessons', function (Blueprint $table) {
            $table->dropColumn(['summary', 'cover_image_path', 'doc_links']);
        });
    }
};
