<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClassEnrollment extends Model
{
    public const ENROLLMENT_TYPE_DAY = 'day';

    public const ENROLLMENT_TYPE_BOARDING = 'boarding';

    public const ENROLLMENT_TYPES = [
        self::ENROLLMENT_TYPE_DAY,
        self::ENROLLMENT_TYPE_BOARDING,
    ];

    public const STATUS_PENDING = 'pending';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_COMPLETED = 'completed';

    protected $fillable = ['school_class_id', 'student_id', 'enrolled_at', 'left_at', 'status', 'enrollment_type'];

    protected $casts = ['enrolled_at' => 'date', 'left_at' => 'date'];

    public function schoolClass(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function scopeEnrolledDuring(Builder $query, mixed $startsAt, mixed $endsAt): Builder
    {
        return $query
            ->whereIn('status', [self::STATUS_ACTIVE, self::STATUS_COMPLETED])
            ->whereDate('enrolled_at', '<=', $endsAt)
            ->where(function (Builder $dates) use ($startsAt): void {
                $dates->whereNull('left_at')->orWhereDate('left_at', '>=', $startsAt);
            });
    }
}
