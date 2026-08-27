<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExaminationQuestion extends Model
{
    protected $fillable = ['examination_id', 'question_id', 'sequence', 'marks'];

    protected $casts = [
        'marks' => 'decimal:2',
    ];

    public function examination(): BelongsTo
    {
        return $this->belongsTo(Examination::class);
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }
}
