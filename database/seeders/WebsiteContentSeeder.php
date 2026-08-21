<?php

namespace Database\Seeders;

use App\Models\{Teacher, User, WebsiteEvent, WebsiteGalleryAlbum, WebsiteGalleryImage, WebsiteInquiry, WebsiteNewsPost, WebsitePage, WebsiteSetting};
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class WebsiteContentSeeder extends Seeder
{
    public function run(): void
    {
        $editor = User::role('super_admin')->first() ?? User::role('school_admin')->first() ?? User::firstOrFail();
        WebsiteSetting::updateOrCreate(['id' => WebsiteSetting::query()->value('id')], [
            'site_name' => 'BrightStar Academy', 'tagline' => 'Nurturing curious minds and confident leaders.', 'email' => 'hello@brightstar.academy', 'phone' => '+233 20 000 0000', 'address' => '12 School Avenue, Accra, Ghana', 'map_latitude' => 5.6037, 'map_longitude' => -0.1870, 'social_links' => ['facebook' => 'https://facebook.com/brightstaracademy', 'instagram' => 'https://instagram.com/brightstaracademy', 'youtube' => 'https://youtube.com/@brightstaracademy'], 'primary_color' => '#123b63', 'secondary_color' => '#0b1f33', 'accent_color' => '#f2a93b',
        ]);
        $pages = [
            'home' => ['hero_title' => 'A brighter beginning for every learner.', 'hero_subtitle' => 'BrightStar Academy combines strong foundations, joyful discovery, and a caring community from early years through basic education.', 'stats' => [['label' => 'Years of learning', 'value' => '21'], ['label' => 'Active learners', 'value' => '240'], ['label' => 'Dedicated teachers', 'value' => '28']], 'programs' => [['title' => 'Early years', 'description' => 'Playful routines that build language, confidence, and curiosity.'], ['title' => 'Primary years', 'description' => 'Strong literacy, numeracy, science, and creative foundations.'], ['title' => 'Upper basic', 'description' => 'Challenge, mentoring, and practical skills for the next step.']]],
            'about' => ['hero_title' => 'A school built around the whole child.', 'hero_subtitle' => 'Since 2005, BrightStar has helped children grow into thoughtful, capable, and compassionate young people.', 'content' => ['mission' => 'We provide a safe, ambitious learning environment where every child is known, supported, and encouraged to make a positive difference.']],
            'academics' => ['hero_title' => 'Learning that connects ideas to life.', 'hero_subtitle' => 'Our curriculum balances core knowledge with creativity, collaboration, technology, and the confidence to ask good questions.', 'programs' => [['title' => 'Literacy and languages', 'description' => 'Reading, writing, speaking, and listening with purpose.'], ['title' => 'Mathematics and science', 'description' => 'Reasoning, investigation, and problem solving through practical work.'], ['title' => 'Creative and physical education', 'description' => 'Art, music, sport, and wellbeing help learners thrive.']]],
            'admissions' => ['hero_title' => 'Take the next step with BrightStar.', 'hero_subtitle' => 'Our admissions team will help your family understand the school, find the right class, and prepare for a confident start.', 'content' => ['steps' => ['Send an enquiry', 'Visit the school', 'Complete the application', 'Begin the journey']]],
        ];
        foreach ($pages as $slug => $data) WebsitePage::updateOrCreate(['slug' => $slug], array_merge($data, ['updated_by' => $editor->id]));

        $posts = [['A new term of possibility', 'Our classrooms are ready for a term of discovery, friendship, and purposeful learning.'], ['BrightStar learners shine at science showcase', 'Families joined us to celebrate experiments, models, and thoughtful questions from across the school.'], ['Reading together, growing together', 'Our new reading challenge gives every learner a chance to discover a story that stays with them.'], ['Community day brings families together', 'Music, games, food, and conversation made this year’s community day a joyful afternoon.'], ['Student leaders launch kindness week', 'Learners are leading small acts of welcome and care throughout the school.'], ['Creative arts evening announced', 'Save the date for an evening of music, movement, visual art, and student storytelling.'], ['Our garden project takes root', 'Science and sustainability are coming alive as classes care for the school garden.']];
        foreach ($posts as $index => [$title, $body]) WebsiteNewsPost::updateOrCreate(['slug' => Str::slug($title)], ['title' => $title, 'excerpt' => Str::limit($body, 140), 'body' => '<p>'.$body.'</p>', 'published_at' => now()->subDays(7 * $index), 'created_by' => $editor->id]);
        $events = [['Open classroom morning', 7, 'Main campus'], ['Community reading day', 18, 'Library courtyard'], ['Creative arts evening', 32, 'School hall'], ['Family wellbeing workshop', 48, 'Learning centre'], ['End-of-term celebration', -14, 'Main campus']];
        foreach ($events as $index => [$title, $days, $location]) WebsiteEvent::updateOrCreate(['slug' => Str::slug($title)], ['title' => $title, 'description' => 'Join learners, families, and staff for a welcoming school community experience.', 'starts_at' => ($days < 0 ? now()->subDays(abs($days)) : now()->addDays($days))->setTime(9, 0), 'ends_at' => ($days < 0 ? now()->subDays(abs($days)) : now()->addDays($days))->setTime(13, 0), 'location' => $location, 'is_published' => true, 'created_by' => $editor->id]);
        foreach ([['Ama Mensah', 'ama.mensah@example.com', 'We would like to arrange a campus tour for our daughter.'], ['Kojo Owusu', 'kojo.owusu@example.com', 'Please share the admissions timeline for the next school year.'], ['Esi Boateng', 'esi.boateng@example.com', 'Are there places available in the upper basic programme?']] as [$name, $email, $message]) WebsiteInquiry::firstOrCreate(['email' => $email, 'message' => $message], ['name' => $name, 'is_read' => false]);
        foreach (['Learning in action', 'Creative studio'] as $title) {
            $album = WebsiteGalleryAlbum::firstOrCreate(['title' => $title], ['description' => 'Moments from life and learning at BrightStar Academy.']);
            foreach (range(1, 8) as $order) WebsiteGalleryImage::firstOrCreate(['album_id' => $album->id, 'sort_order' => $order], ['path' => '', 'caption' => $title.' moment '.$order]);
        }
        foreach (Teacher::where('status', 'active')->orderBy('id')->limit(10)->get() as $order => $teacher) $teacher->update(['is_featured_on_website' => true, 'website_display_order' => $order + 1, 'public_bio' => 'A caring educator who helps learners connect ideas, build confidence, and enjoy the process of discovery.']);
    }
}
