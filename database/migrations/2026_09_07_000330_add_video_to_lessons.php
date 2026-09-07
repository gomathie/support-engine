<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Video lessons (PA-9).
 *
 * The original brief said there was no video requirement; the competency plan
 * reverses that — §4.1 makes 5–7 minute video the default delivery format for
 * re-aligned content, with embeds now and native upload in PA-10.
 *
 * The provider and id are stored separately rather than as a pasted URL, because
 * the embed URL is rebuilt from a fixed template at render time. A stored URL
 * would be untrusted input feeding an iframe src. See App\Support\Video\VideoEmbed.
 *
 * `video_provider` is left open for 'upload' in PA-10, so that ticket adds a
 * path column rather than reworking these.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lessons', function (Blueprint $table) {
            $table->string('video_provider', 20)->nullable()->after('external_url');
            $table->string('video_id', 64)->nullable()->after('video_provider');

            // §4.1 sets a 5–7 minute ceiling. Stored so the audit can report on
            // it; the author enters it, since neither provider will tell us
            // without an API key.
            $table->unsignedInteger('video_duration_seconds')->nullable()->after('video_id');

            // Adults scan before they watch, and PILOT terminology defeats
            // auto-captioning — so the transcript is authored, not generated.
            $table->longText('video_transcript')->nullable()->after('video_duration_seconds');

            $table->index('video_provider');
        });
    }

    public function down(): void
    {
        Schema::table('lessons', function (Blueprint $table) {
            $table->dropIndex(['video_provider']);
            $table->dropColumn([
                'video_provider',
                'video_id',
                'video_duration_seconds',
                'video_transcript',
            ]);
        });
    }
};
