<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('homeworks', function (Blueprint $table) {
            $table->dropForeign(['class_id']);
            $table->foreignId('subject_id')->constrained('subjects')->cascadeOnDelete();
            $table->unique(['class_id', 'subject_id', 'date'], 'homeworks_unique_class_subject_date');
            $table->foreign('class_id')->references('id')->on('classes')->cascadeOnDelete();
        });

        Schema::table('weekly_schedule_entries', function (Blueprint $table) {
            $table->foreignId('subject_id')->constrained('subjects')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('homeworks', function (Blueprint $table) {
            $table->dropForeign(['class_id']);
            $table->dropForeign(['subject_id']);
            $table->dropIndex('homeworks_unique_class_subject_date');
            $table->dropColumn('subject_id');
            $table->foreign('class_id')->references('id')->on('classes')->cascadeOnDelete();
        });

        Schema::table('weekly_schedule_entries', function (Blueprint $table) {
            $table->dropForeign(['subject_id']);
            $table->dropColumn('subject_id');
        });
    }
};
