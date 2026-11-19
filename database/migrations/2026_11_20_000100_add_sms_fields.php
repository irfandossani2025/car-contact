<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('phone', 20)->nullable()->after('telegram_link_token');
            $table->timestamp('phone_verified_at')->nullable()->after('phone');
            $table->boolean('notify_sms')->default(false)->after('phone_verified_at');
            $table->string('phone_verify_code')->nullable()->after('notify_sms');
            $table->timestamp('phone_verify_expires_at')->nullable()->after('phone_verify_code');
            $table->unsignedTinyInteger('phone_verify_attempts')->default(0)->after('phone_verify_expires_at');
        });

        Schema::table('alerts', function (Blueprint $table) {
            // Short, unguessable token for the owner's reply link in SMS (keeps the text to one message).
            $table->string('owner_token', 16)->nullable()->unique()->after('public_token');
        });
    }

    public function down(): void
    {
        Schema::table('alerts', function (Blueprint $table) {
            $table->dropUnique(['owner_token']);
            $table->dropColumn('owner_token');
        });
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['phone', 'phone_verified_at', 'notify_sms', 'phone_verify_code', 'phone_verify_expires_at', 'phone_verify_attempts']);
        });
    }
};
