<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Thirteen symptoms is past the point where a flat list is scannable under
     * pressure, which is exactly when this panel gets used. Grouping them lets
     * an agent jump to the right family first and read four options instead of
     * thirteen.
     */
    public function up(): void
    {
        Schema::table('diagnostic_trees', function (Blueprint $table) {
            $table->string('category')->nullable()->after('question');
            $table->index('category');
        });
    }

    public function down(): void
    {
        Schema::table('diagnostic_trees', function (Blueprint $table) {
            $table->dropIndex(['category']);
            $table->dropColumn('category');
        });
    }
};
