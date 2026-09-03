<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sms_campaigns', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('school_class_id')->nullable()->constrained('school_classes')->nullOnDelete();
            $table->string('mode', 20);
            $table->json('audiences');
            $table->json('filters')->nullable();
            $table->text('message');
            $table->string('sender_id')->nullable();
            $table->string('provider')->nullable();
            $table->string('encoding', 20);
            $table->unsignedInteger('character_count')->default(0);
            $table->unsignedTinyInteger('segment_count')->default(1);
            $table->string('status', 20)->default('queued');
            $table->unsignedInteger('recipient_count')->default(0);
            $table->unsignedInteger('sent_count')->default(0);
            $table->unsignedInteger('failed_count')->default(0);
            $table->unsignedInteger('skipped_count')->default(0);
            $table->timestamp('queued_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['school_id', 'status']);
            $table->index(['school_id', 'created_at']);
        });

        Schema::create('sms_recipients', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('sms_campaign_id')->constrained()->cascadeOnDelete();
            $table->string('audience', 20);
            $table->string('recipient_type', 30);
            $table->unsignedBigInteger('recipient_id')->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('recipient_name');
            $table->string('phone')->nullable();
            $table->string('normalized_phone')->nullable();
            $table->string('phone_source')->nullable();
            $table->string('status', 20)->default('queued');
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->text('last_error')->nullable();
            $table->string('skip_reason')->nullable();
            $table->string('provider_message_id')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->timestamps();

            $table->unique(['sms_campaign_id', 'normalized_phone'], 'sms_campaign_normalized_unique');
            $table->index(['sms_campaign_id', 'status']);
            $table->index(['recipient_type', 'recipient_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sms_recipients');
        Schema::dropIfExists('sms_campaigns');
    }
};
