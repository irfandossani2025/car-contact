<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('whatsapp_number', 20)->nullable()->after('phone_verify_attempts');
            $table->string('whatsapp_link_code', 12)->nullable()->unique()->after('whatsapp_number');
            $table->boolean('notify_whatsapp')->default(false)->after('whatsapp_link_code');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['whatsapp_link_code']);
            $table->dropColumn(['whatsapp_number', 'whatsapp_link_code', 'notify_whatsapp']);
        });
    }
};
