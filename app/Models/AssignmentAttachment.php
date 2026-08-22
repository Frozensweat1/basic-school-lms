<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssignmentAttachment extends Model
{
    protected $fillable = ['assignment_id', 'name', 'disk', 'path', 'size'];
    public function assignment(): BelongsTo
    {
        return $this->belongsTo(Assignment::class);
    }
}
