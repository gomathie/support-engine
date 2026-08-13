<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The Support Panel. Its decision trees were the TREES const in
     * pages/support-panel/script.js; its per-case working state was the S object,
     * which never survived a reload. Trees become editable content, cases become
     * a persisted history the employee can return to.
     */
    public function up(): void
    {
        Schema::create('diagnostic_trees', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();

            // The symptom in the customer's words — "I can't log in".
            $table->string('question');

            // Display label for the layer range this symptom usually lives in.
            $table->string('layer_label', 20)->nullable();

            $table->text('description')->nullable();
            $table->unsignedInteger('position')->default(0);
            $table->boolean('is_published')->default(true);

            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('diagnostic_steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('diagnostic_tree_id')->constrained()->cascadeOnDelete();

            $table->text('prompt');

            // 1–7 in the layer model: access, contract, interface, object,
            // sensor, device, relay.
            $table->unsignedTinyInteger('layer');

            // What it means when this step is the cause. Revealed only once the
            // employee marks the step as "cause found", matching the prototype.
            $table->text('fix')->nullable();

            $table->unsignedInteger('position')->default(0);
            $table->timestamps();

            $table->index(['diagnostic_tree_id', 'position']);
        });

        Schema::create('support_cases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('diagnostic_tree_id')->nullable()->constrained()->nullOnDelete();

            $table->string('customer')->nullable();
            $table->string('object_ref')->nullable();

            // { "<step id>": "out" | "found" }
            $table->jsonb('step_states')->default('{}');

            $table->string('priority_code', 4)->nullable();   // P1..P5
            $table->string('priority_reason')->nullable();

            $table->text('customer_told')->nullable();

            $table->string('status', 20)->default('open');    // open|escalated|closed
            $table->timestamp('closed_at')->nullable();

            $table->timestamps();

            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('support_cases');
        Schema::dropIfExists('diagnostic_steps');
        Schema::dropIfExists('diagnostic_trees');
    }
};
