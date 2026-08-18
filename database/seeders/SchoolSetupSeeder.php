<?php

namespace Database\Seeders;

use App\Models\AcademicYear;
use App\Models\SchoolClass;
use App\Models\School;
use App\Models\Subject;
use Illuminate\Database\Seeder;

class SchoolSetupSeeder extends Seeder
{
    public function run(): void
    {
        $school = School::firstOrCreate(['code' => 'BRIGHTSTAR'], ['name' => 'BrightStar Academy']);

        $academicYear = AcademicYear::firstOrCreate([
            'school_id' => $school->id,
            'name' => '2026/2027',
        ], [
            'starts_at' => '2026-09-01',
            'ends_at' => '2027-07-31',
            'is_active' => true,
            'is_locked' => false,
        ]);

        $academicYear->terms()->firstOrCreate([
            'name' => 'Term 1',
        ], [
            'starts_at' => '2026-09-01',
            'ends_at' => '2026-12-18',
            'is_active' => true,
            'is_locked' => false,
        ]);

        foreach (['KG 1', 'KG 2', 'Basic 1', 'Basic 2', 'Basic 3', 'Basic 4', 'Basic 5', 'Basic 6'] as $className) {
            SchoolClass::firstOrCreate([
                'academic_year_id' => $academicYear->id,
                'name' => $className,
            ], [
                'status' => 'active',
            ]);
        }

        foreach ([
            ['name' => 'English', 'code' => 'ENG'],
            ['name' => 'Mathematics', 'code' => 'MATH'],
            ['name' => 'Science', 'code' => 'SCI'],
            ['name' => 'Social Studies', 'code' => 'SOC'],
            ['name' => 'Creative Arts', 'code' => 'ART'],
        ] as $subject) {
            Subject::firstOrCreate(['school_id' => $school->id, 'code' => $subject['code']], [
                'name' => $subject['name'],
                'description' => null,
                'is_active' => true,
            ]);
        }
    }
}
