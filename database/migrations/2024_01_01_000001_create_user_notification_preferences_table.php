<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_notification_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('channel_name'); // email, sms, push, slack, etc
            $table->string('notification_type'); // order.shipped, user.mentioned, etc
            $table->boolean('is_enabled')->default(true);
            $table->timestamps();

            // Prevent duplicates: one preference per user, channel, and notification type
            $table->unique(['user_id', 'channel_name', 'notification_type'], 'user_channel_type_unique');

            // Indexes for performance
            $table->index(['user_id', 'notification_type']);
            $table->index('is_enabled');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_notification_preferences');
    }
};
