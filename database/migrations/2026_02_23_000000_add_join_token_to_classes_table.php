<?php

declare(strict_types=1);

use App\Models\Classroom;
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
        Schema::table('classes', function (Blueprint $table): void {
            $table->string('join_token', 16)
                ->after('code')
                ->unique();
        });

        Classroom::query()
            ->whereNull('join_token')
            ->each(function (Classroom $classroom): void {
                $classroom->join_token = Classroom::generateJoinToken();
                $classroom->save();
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('classes', function (Blueprint $table): void {
            $table->dropUnique('classes_join_token_unique');
            $table->dropColumn('join_token');
        });
    }
};
