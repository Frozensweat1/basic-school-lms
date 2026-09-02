<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('teachers', function (Blueprint $table): void {
            $table->string('gender', 20)->nullable()->after('last_name');
            $table->date('date_of_birth')->nullable()->after('gender');
            $table->string('nationality')->nullable()->after('date_of_birth');
            $table->string('postal_address')->nullable()->after('phone');
            $table->string('residential_address')->nullable()->after('postal_address');
            $table->string('gps_address')->nullable()->after('residential_address');
            $table->string('marital_status', 30)->nullable()->after('gps_address');
            $table->string('religion')->nullable()->after('marital_status');
            $table->string('emergency_contact_name')->nullable()->after('religion');
            $table->string('emergency_contact_phone')->nullable()->after('emergency_contact_name');
            $table->string('ssnit_number')->nullable()->after('emergency_contact_phone');
            $table->string('ghana_card_number')->nullable()->after('ssnit_number');
        });

        Schema::create('teacher_dependants', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('teacher_id')->constrained()->cascadeOnDelete();
            $table->string('relation')->nullable();
            $table->string('name')->nullable();
            $table->date('date_of_birth')->nullable();
            $table->boolean('is_next_of_kin')->default(false);
            $table->timestamps();
        });

        Schema::create('teacher_qualifications', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('teacher_id')->constrained()->cascadeOnDelete();
            $table->string('qualification')->nullable();
            $table->string('institution')->nullable();
            $table->string('program_of_study')->nullable();
            $table->string('year_of_graduation', 10)->nullable();
            $table->timestamps();
        });

        Schema::create('teacher_work_experiences', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('teacher_id')->constrained()->cascadeOnDelete();
            $table->string('institution')->nullable();
            $table->string('country')->nullable();
            $table->string('position')->nullable();
            $table->string('address')->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->timestamps();
        });

        Schema::create('teacher_referees', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('teacher_id')->constrained()->cascadeOnDelete();
            $table->string('name')->nullable();
            $table->string('contact')->nullable();
            $table->string('place_of_work')->nullable();
            $table->string('position')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('teacher_referees');
        Schema::dropIfExists('teacher_work_experiences');
        Schema::dropIfExists('teacher_qualifications');
        Schema::dropIfExists('teacher_dependants');

        Schema::table('teachers', function (Blueprint $table): void {
            $table->dropColumn([
                'gender', 'date_of_birth', 'nationality', 'postal_address',
                'residential_address', 'gps_address', 'marital_status', 'religion',
                'emergency_contact_name', 'emergency_contact_phone', 'ssnit_number', 'ghana_card_number',
            ]);
        });
    }
};
