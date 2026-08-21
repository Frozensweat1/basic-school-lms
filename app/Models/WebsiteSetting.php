<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class WebsiteSetting extends Model { protected $fillable=['site_name','tagline','logo_path','email','phone','address','map_latitude','map_longitude','social_links','primary_color','secondary_color','accent_color']; protected $casts=['social_links'=>'array','map_latitude'=>'float','map_longitude'=>'float']; }
