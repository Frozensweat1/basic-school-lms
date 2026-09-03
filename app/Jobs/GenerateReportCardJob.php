<?php

namespace App\Jobs;

use App\Models\Student;
use App\Models\Term;
use App\Services\Reports\ReportCardGenerator;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class GenerateReportCardJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 120;

    public function __construct(
        public Student $student,
        public Term $term,
        public int $schoolClassId,
    ) {
        $this->onQueue('default');
    }

    public function backoff(): array
    {
        return [10, 30, 60];
    }

    public function handle(ReportCardGenerator $generator): void
    {
        $generator->generate($this->student, $this->term, $this->schoolClassId);
    }
}
