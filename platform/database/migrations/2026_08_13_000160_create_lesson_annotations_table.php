<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Replaces the prototype's runtime DOM scan for `.std` markers
     * (pages/skills-module/script.js), which rebuilt the index on every page load
     * and derived each marker's section by walking previous siblings. Annotations
     * are now first-class rows an author can edit.
     */
    public function up(): void
    {
        Schema::create('lesson_annotations', function (Blueprint $table) {
            $table->id();

            $table->foreignId('lesson_id')->constrained()->cascadeOnDelete();

            // standard_default — an industry-standard answer supplied by the trainer,
            //                    to be confirmed locally
            // gap             — a company-specific blank that still needs an answer
            // note            — plain annotation
            $table->string('type', 30)->default('standard_default');

            // The id of the element in the lesson body this annotation points at,
            // so the drawer can scroll to it.
            $table->string('anchor', 100);

            $table->string('section_label')->nullable();
            $table->text('body');

            $table->boolean('is_resolved')->default(false);
            $table->unsignedInteger('position')->default(0);

            $table->timestamps();

            $table->unique(['lesson_id', 'anchor']);
            $table->index(['lesson_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lesson_annotations');
    }
};
