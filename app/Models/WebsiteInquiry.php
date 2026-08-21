<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class WebsiteInquiry extends Model { protected $fillable=['name','email','message','is_read']; protected $casts=['is_read'=>'boolean']; }
