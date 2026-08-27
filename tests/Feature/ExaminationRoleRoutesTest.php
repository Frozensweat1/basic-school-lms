<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExaminationRoleRoutesTest extends TestCase
{
    public function test_examination_list_routes_exist_for_all_roles(): void
    {
        $this->assertNotNull(route('lms.examinations.admin.index'));
        $this->assertNotNull(route('lms.examinations.teacher.index'));
        $this->assertNotNull(route('lms.examinations.student.index'));
        $this->assertNotNull(route('lms.examinations.parent.index'));
    }

    public function test_examination_questions_routes_exist_for_staff(): void
    {
        $this->assertTrue(\Illuminate\Support\Facades\Route::has('lms.examinations.admin.questions.index'));
        $this->assertTrue(\Illuminate\Support\Facades\Route::has('lms.examinations.teacher.questions.index'));
    }

    public function test_examination_scores_routes_exist_for_staff(): void
    {
        $this->assertTrue(\Illuminate\Support\Facades\Route::has('lms.examinations.admin.scores.index'));
        $this->assertTrue(\Illuminate\Support\Facades\Route::has('lms.examinations.teacher.scores.index'));
    }
}
