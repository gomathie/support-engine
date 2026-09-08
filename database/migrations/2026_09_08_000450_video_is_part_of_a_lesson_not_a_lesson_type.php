<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Video stops being a lesson *type* and becomes part of a lesson.
 *
 * `video_embed` and `video_upload` were types, and the viewer branched on type —
 * so a lesson carrying a video rendered the player *instead of* its text. The
 * video stood alone with nothing explaining it, which is the opposite of what
 * §4.1 asks for: video as the delivery format for the material, not a
 * substitute for it.
 *
 * Any lesson may now carry a video, shown above its body. The type describes the
 * body alone, so lessons on the retired types become `rich_text` — their
 * `video_provider` / `video_id` / `video_path` columns are untouched and keep
 * working exactly as before.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('lessons')
            ->whereIn('type', ['video_embed', 'video_upload'])
            ->update(['type' => 'rich_text', 'updated_at' => now()]);
    }

    public function down(): void
    {
        /*
         * Not reversed. Which lessons were typed as video before this ran is
         * not recorded anywhere, and the video columns alone cannot tell us —
         * a lesson may legitimately carry both a video and a written body now,
         * which is the entire point of the change.
         */
    }
};
