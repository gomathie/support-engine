<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lesson_resources', function (Blueprint $table) {
            $table->id();

            $table->foreignId('lesson_id')->constrained()->cascadeOnDelete();

            $table->string('name');
            $table->text('description')->nullable();

            // Disk is stored per row rather than read from config at download time,
            // so moving new uploads to S3 later does not orphan existing files.
            $table->string('disk', 40)->default('private');
            $table->string('path');

            $table->string('original_filename')->nullable();
            $table->string('mime_type', 120)->nullable();
            $table->unsignedBigInteger('size_bytes')->nullable();

            // False for files that are only rendered inline (an image in the page,
            // a PDF in the viewer) and should not be offered as a download.
            $table->boolean('is_downloadable')->default(true);

            $table->unsignedInteger('position')->default(0);

            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();

            $table->index(['lesson_id', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lesson_resources');
    }
};
