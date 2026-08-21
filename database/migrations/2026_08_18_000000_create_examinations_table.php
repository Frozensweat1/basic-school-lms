<?php
use Illuminate\Database\Migrations\Migration; use Illuminate\Database\Schema\Blueprint; use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up():void
    {
        Schema::create('examinations',function(Blueprint $table)
        {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('academic_year_id')->constrained()->cascadeOnDelete();
            $table->foreignId('term_id')->constrained()->cascadeOnDelete();
            $table->foreignId('class_subject_id')->constrained()->cascadeOnDelete();
            $table->foreignId('teacher_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->date('exam_date');
            $table->unsignedInteger('duration_minutes')->nullable();
            $table->decimal('max_score',8,2)->default(100);
            $table->string('status')->default('draft');
            $table->timestamps();
            $table->index(['school_id','term_id','status']);
            }
            );
            }
            public function down():void
            {
                Schema::dropIfExists('examinations');
                }
                };
