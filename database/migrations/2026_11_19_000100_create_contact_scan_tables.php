<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('notify_email')->default(true)->after('password');
            $table->string('telegram_chat_id')->nullable()->after('notify_email');
            $table->string('telegram_link_token', 40)->nullable()->unique()->after('telegram_chat_id');
        });

        Schema::create('vehicles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name', 60);
            $table->string('plate', 20)->nullable();
            $table->string('color', 30)->nullable();
            $table->string('tag_code', 12)->unique();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehicle_id')->constrained()->cascadeOnDelete();
            $table->string('public_token', 32)->unique();
            $table->string('reason', 30);
            $table->string('message', 280)->nullable();
            $table->string('sender_hash', 64)->index();
            $table->string('reply', 30)->nullable();
            $table->timestamp('seen_at')->nullable();
            $table->timestamp('replied_at')->nullable();
            $table->text('delivery')->nullable(); // text, not json: MariaDB 10.1 has no JSON type
            $table->timestamps();
            $table->index(['vehicle_id', 'created_at']);
        });

        Schema::create('push_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('endpoint_hash', 64)->unique();
            $table->text('endpoint');
            $table->string('public_key');
            $table->string('auth_token');
            $table->string('content_encoding', 20)->default('aes128gcm');
            $table->string('user_agent')->nullable();
            $table->timestamps();
        });

        Schema::create('blocked_senders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('sender_hash', 64);
            $table->timestamps();
            $table->unique(['user_id', 'sender_hash']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('blocked_senders');
        Schema::dropIfExists('push_subscriptions');
        Schema::dropIfExists('alerts');
        Schema::dropIfExists('vehicles');
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['telegram_link_token']);
            $table->dropColumn(['notify_email', 'telegram_chat_id', 'telegram_link_token']);
        });
    }
};
