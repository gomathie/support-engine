<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Native video upload (PA-10) — the second of the two video methods.
 *
 * Files land on the `private` disk, which has no URL and no public visibility,
 * exactly as lesson_resources already do. The only route to the bytes is a
 * controller that has run a policy first. This extends that mechanism rather
 * than inventing a second one.
 *
 * `video_disk` is recorded per row so that pointing PRIVATE_FILESYSTEM_DRIVER at
 * S3 later does not strand the files already written to local storage.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lessons', function (Blueprint $table) {
            $table->string('video_disk', 30)->nullable()->after('video_transcript');
            $table->string('video_path')->nullable()->after('video_disk');
            $table->string('video_original_name')->nullable()->after('video_path');
            $table->string('video_mime_type', 100)->nullable()->after('video_original_name');

            // Bytes. The 500 MB ceiling in §5.1 exceeds a 4-byte int.
            $table->unsignedBigInteger('video_size_bytes')->nullable()->after('video_mime_type');

            // processing | ready | failed. Null for an embed, which has nothing
            // to process. Transcoding is not built yet, so an upload goes
            // straight to ready — the column exists so PA-10's queued pipeline
            // does not need another migration.
            $table->string('video_status', 20)->nullable()->after('video_size_bytes');
        });
    }

    public function down(): void
    {
        Schema::table('lessons', function (Blueprint $table) {
            $table->dropColumn([
                'video_disk',
                'video_path',
                'video_original_name',
                'video_mime_type',
                'video_size_bytes',
                'video_status',
            ]);
        });
    }
};
