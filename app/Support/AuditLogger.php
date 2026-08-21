<?php

namespace App\Support;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;

class AuditLogger
{
    public function record(string $event, ?Model $auditable = null, array $oldValues = [], array $newValues = [], ?int $schoolId = null): AuditLog
    {
        $user = auth()->user();
        $schoolId ??= $auditable?->school_id;

        return AuditLog::create([
            'user_id' => $user?->id,
            'school_id' => $schoolId,
            'event' => $event,
            'auditable_type' => $auditable?->getMorphClass(),
            'auditable_id' => $auditable?->getKey(),
            'old_values' => $oldValues ?: null,
            'new_values' => $newValues ?: null,
            'ip_address' => request()?->ip(),
            'user_agent' => request()?->userAgent(),
        ]);
    }
}
