<?php

namespace App\Livewire\LMS\Website;

use App\Models\WebsiteSetting;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Jantinnerezo\LivewireAlert\Facades\LivewireAlert;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;
use Throwable;

#[Layout('layouts.lms')]
class Settings extends Component
{
    use WithFileUploads;
    public string $siteName = '', $tagline = '', $email = '', $phone = '', $address = '', $latitude = '', $longitude = '', $primaryColor = '#1e3a8a', $secondaryColor = '#0f172a', $accentColor = '#f59e0b';
    public string $facebook = '', $instagram = '', $youtube = '';
    public $logo;

    public function mount(): void { $this->authorizeWebsite(); $settings = WebsiteSetting::first() ?? WebsiteSetting::create(['site_name' => 'BrightStar Academy']); $this->siteName = $settings->site_name; $this->tagline = $settings->tagline ?? ''; $this->email = $settings->email ?? ''; $this->phone = $settings->phone ?? ''; $this->address = $settings->address ?? ''; $this->latitude = (string) ($settings->map_latitude ?? ''); $this->longitude = (string) ($settings->map_longitude ?? ''); $this->primaryColor = $settings->primary_color; $this->secondaryColor = $settings->secondary_color; $this->accentColor = $settings->accent_color; $this->facebook = $settings->social_links['facebook'] ?? ''; $this->instagram = $settings->social_links['instagram'] ?? ''; $this->youtube = $settings->social_links['youtube'] ?? ''; }
    public function save(): void { $this->authorizeWebsite(); try { $data = $this->validate(['siteName' => ['required','string','max:255'],'tagline'=>['nullable','string','max:500'],'email'=>['nullable','email','max:255'],'phone'=>['nullable','string','max:50'],'address'=>['nullable','string','max:1000'],'latitude'=>['nullable','numeric','between:-90,90'],'longitude'=>['nullable','numeric','between:-180,180'],'primaryColor'=>['required','regex:/^#[0-9A-Fa-f]{6}$/'],'secondaryColor'=>['required','regex:/^#[0-9A-Fa-f]{6}$/'],'accentColor'=>['required','regex:/^#[0-9A-Fa-f]{6}$/'],'facebook'=>['nullable','url','max:500'],'instagram'=>['nullable','url','max:500'],'youtube'=>['nullable','url','max:500'],'logo'=>['nullable','image','max:2048']]); $settings=WebsiteSetting::firstOrFail(); $logoPath=$settings->logo_path; if($this->logo)$logoPath=$this->logo->store('website','public'); $settings->update(['site_name'=>$data['siteName'],'tagline'=>$data['tagline'],'email'=>$data['email'],'phone'=>$data['phone'],'address'=>$data['address'],'map_latitude'=>$data['latitude'] ?: null,'map_longitude'=>$data['longitude'] ?: null,'primary_color'=>$data['primaryColor'],'secondary_color'=>$data['secondaryColor'],'accent_color'=>$data['accentColor'],'social_links'=>['facebook'=>$data['facebook'],'instagram'=>$data['instagram'],'youtube'=>$data['youtube']],'logo_path'=>$logoPath]); if($this->logo && $settings->getOriginal('logo_path'))Storage::disk('public')->delete($settings->getOriginal('logo_path')); $this->logo=null; LivewireAlert::title('Website settings saved')->success()->asToast()->show(); } catch(ValidationException $e){throw $e;} catch(Throwable $e){report($e);LivewireAlert::title('Unable to save website settings')->error()->asToast()->show();} }
    private function authorizeWebsite(): void { abort_unless(auth()->user()->hasPermissionTo('manage website content'), 403); }
    public function render(){return view('livewire.lms.website.settings');}
}
