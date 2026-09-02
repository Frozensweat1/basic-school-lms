<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table): void {
            $table->string('home_town')->nullable();
            $table->string('region')->nullable();
            $table->string('nationality')->nullable();
            $table->string('denomination')->nullable();
            $table->string('health_insurance_id')->nullable();
            $table->string('previous_school_name')->nullable();
            $table->string('previous_school_city')->nullable();
            $table->string('previous_school_country')->nullable();
            $table->string('previous_school_gps_address')->nullable();
            $table->string('previous_school_phone')->nullable();
            $table->string('previous_school_last_class')->nullable();
            $table->boolean('has_allergies')->default(false);
            $table->text('allergy_details')->nullable();
        });

        Schema::table('parents', function (Blueprint $table): void {
            $table->string('gps_address')->nullable();
            $table->string('city')->nullable();
            $table->string('workplace')->nullable();
            $table->string('ghana_card_number')->nullable();
        });

        Schema::table('class_enrollments', function (Blueprint $table): void {
            $table->string('enrollment_type')->default('day');
        });

        Schema::table('parent_student', function (Blueprint $table): void {
            $table->date('information_date')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('parent_student', function (Blueprint $table): void {
            $table->dropColumn('information_date');
        });

        Schema::table('class_enrollments', function (Blueprint $table): void {
            $table->dropColumn('enrollment_type');
        });

        Schema::table('parents', function (Blueprint $table): void {
            $table->dropColumn([
                'gps_address',
                'city',
                'workplace',
                'ghana_card_number',
            ]);
        });

        Schema::table('students', function (Blueprint $table): void {
            $table->dropColumn([
                'home_town',
                'region',
                'nationality',
                'denomination',
                'health_insurance_id',
                'previous_school_name',
                'previous_school_city',
                'previous_school_country',
                'previous_school_gps_address',
                'previous_school_phone',
                'previous_school_last_class',
                'has_allergies',
                'allergy_details',
            ]);
        });
    }
};
