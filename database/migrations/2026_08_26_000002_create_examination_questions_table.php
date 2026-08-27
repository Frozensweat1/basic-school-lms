<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('examination_questions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('examination_id')->constrained()->cascadeOnDelete();
            $table->foreignId('question_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('sequence')->default(0);
            $table->decimal('marks', 8, 2)->default(1);
            $table->timestamps();
            $table->unique(['examination_id', 'question_id']);
            $table->index('examination_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('examination_questions');
    }
};
