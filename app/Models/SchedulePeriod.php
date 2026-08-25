<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

class SchedulePeriod extends Model
{
    protected $fillable = ['school_id', 'name', 'starts_at', 'ends_at', 'sequence'];

    protected $casts = [
        'sequence' => 'integer',
    ];

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function timetableEntries(): HasMany
    {
        return $this->hasMany(TimetableEntry::class);
    }

    public function formattedStart(): string
    {
        return Carbon::createFromFormat('H:i:s', $this->normalisedTime($this->starts_at))->format('g:i A');
    }

    public function formattedEnd(): string
    {
        return Carbon::createFromFormat('H:i:s', $this->normalisedTime($this->ends_at))->format('g:i A');
    }

    public function durationMinutes(): int
    {
        $start = Carbon::createFromFormat('H:i:s', $this->normalisedTime($this->starts_at));
        $end = Carbon::createFromFormat('H:i:s', $this->normalisedTime($this->ends_at));

        return (int) $start->diffInMinutes($end);
    }

    private function normalisedTime(string $time): string
    {
        return strlen($time) === 5 ? "{$time}:00" : $time;
    }
}
