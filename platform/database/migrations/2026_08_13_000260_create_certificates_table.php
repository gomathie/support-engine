<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('certificates', function (Blueprint $table) {
            $table->id();

            // Restricted on delete. A certificate is a record of something that
            // happened; removing the person or retiring the course must not erase
            // the evidence that they passed it.
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->foreignId('course_id')->constrained()->restrictOnDelete();

            $table->string('certificate_number')->unique();

            // Name and title are snapshotted at issue time. If somebody changes
            // their name, or the course is retitled, the PDF already handed out
            // must still match the record.
            $table->string('recipient_name');
            $table->string('course_title');

            $table->decimal('score', 5, 2)->nullable();

            $table->timestamp('completed_at');
            $table->timestamp('issued_at');

            // Populated once the queued render finishes; null means the PDF is
            // still being generated.
            $table->string('disk', 40)->nullable();
            $table->string('path')->nullable();

            // Public, unguessable id for third-party verification without login.
            $table->string('verification_token', 64)->unique();

            $table->timestamps();

            $table->unique(['user_id', 'course_id']);
            $table->index('issued_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('certificates');
    }
};
