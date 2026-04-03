<?php

use App\Models\Classroom;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->unsignedBigInteger('id')->unique();
            $table->string('first_name');
            $table->string('username');
            $table->string('language_code');
            $table->foreignIdFor(Classroom::class, 'class_id')->nullable()->constrained()->cascadeOnUpdate()->onDelete('set null');
            $table->string('role')->default('student');
            $table->boolean('is_bot')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
