<?php
namespace App\Models; use Illuminate\Database\Eloquent\Model; use Illuminate\Database\Eloquent\Relations\BelongsTo;
class SubmissionAttachment extends Model { protected $fillable=['assignment_submission_id','name','disk','path','size']; public function submission():BelongsTo{return $this->belongsTo(AssignmentSubmission::class,'assignment_submission_id');} }
