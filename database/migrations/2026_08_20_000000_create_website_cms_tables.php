<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('website_settings', function (Blueprint $table): void {
            $table->id();
            $table->string('site_name');
            $table->string('tagline')->nullable();
            $table->string('logo_path')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->text('address')->nullable();
            $table->decimal('map_latitude', 10, 7)->nullable();
            $table->decimal('map_longitude', 10, 7)->nullable();
            $table->json('social_links')->nullable();
            $table->string('primary_color', 7)->default('#1e3a8a');
            $table->string('secondary_color', 7)->default('#0f172a');
            $table->string('accent_color', 7)->default('#f59e0b');
            $table->timestamps();
        });
        Schema::create('website_pages', function (Blueprint $table): void {
            $table->id(); $table->string('slug')->unique(); $table->string('hero_title')->nullable(); $table->text('hero_subtitle')->nullable(); $table->string('hero_image_path')->nullable(); $table->json('content')->nullable(); $table->json('stats')->nullable(); $table->json('programs')->nullable(); $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete(); $table->timestamps();
        });
        Schema::create('website_news_posts', function (Blueprint $table): void {
            $table->id(); $table->string('title'); $table->string('slug')->unique(); $table->string('excerpt')->nullable(); $table->longText('body'); $table->string('featured_image_path')->nullable(); $table->timestamp('published_at')->nullable(); $table->foreignId('created_by')->constrained('users')->cascadeOnDelete(); $table->timestamps(); $table->index(['published_at', 'created_at']);
        });
        Schema::create('website_events', function (Blueprint $table): void {
            $table->id(); $table->string('title'); $table->string('slug')->unique(); $table->text('description')->nullable(); $table->dateTime('starts_at'); $table->dateTime('ends_at')->nullable(); $table->string('location')->nullable(); $table->string('featured_image_path')->nullable(); $table->boolean('is_published')->default(false); $table->foreignId('created_by')->constrained('users')->cascadeOnDelete(); $table->timestamps(); $table->index(['is_published', 'starts_at']);
        });
        Schema::create('website_gallery_albums', function (Blueprint $table): void { $table->id(); $table->string('title'); $table->text('description')->nullable(); $table->timestamps(); });
        Schema::create('website_gallery_images', function (Blueprint $table): void { $table->id(); $table->foreignId('album_id')->constrained('website_gallery_albums')->cascadeOnDelete(); $table->string('path'); $table->string('caption')->nullable(); $table->unsignedInteger('sort_order')->default(0); $table->timestamps(); });
        Schema::create('website_inquiries', function (Blueprint $table): void { $table->id(); $table->string('name'); $table->string('email'); $table->text('message'); $table->boolean('is_read')->default(false); $table->timestamps(); $table->index(['is_read', 'created_at']); });
        Schema::table('teachers', function (Blueprint $table): void { $table->boolean('is_featured_on_website')->default(false)->after('status'); $table->text('public_bio')->nullable()->after('is_featured_on_website'); $table->unsignedInteger('website_display_order')->default(0)->after('public_bio'); });
    }

    public function down(): void
    {
        Schema::table('teachers', function (Blueprint $table): void { $table->dropColumn(['is_featured_on_website', 'public_bio', 'website_display_order']); });
        foreach (['website_inquiries', 'website_gallery_images', 'website_gallery_albums', 'website_events', 'website_news_posts', 'website_pages', 'website_settings'] as $table) Schema::dropIfExists($table);
    }
};
