<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('questions', function (Blueprint $table): void {
            $table->foreignId('subject_id')->nullable()->after('school_id')->constrained()->nullOnDelete();
            $table->foreignId('topic_id')->nullable()->after('subject_id')->constrained()->nullOnDelete();
            $table->foreignId('lesson_id')->nullable()->after('topic_id')->constrained()->nullOnDelete();
            $table->index(['school_id', 'subject_id']);
            $table->index(['subject_id', 'topic_id', 'lesson_id']);
        });
    }

    public function down(): void
    {
        Schema::table('questions', function (Blueprint $table): void {
            $table->dropIndex(['school_id', 'subject_id']);
            $table->dropIndex(['subject_id', 'topic_id', 'lesson_id']);
            $table->dropConstrainedForeignId('lesson_id');
            $table->dropConstrainedForeignId('topic_id');
            $table->dropConstrainedForeignId('subject_id');
        });
    }
};
