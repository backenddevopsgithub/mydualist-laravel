<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dua_submissions', function (Blueprint $table) {
            $table->string('whatsapp_country_code', 6)->nullable()->after('is_anonymous');
            $table->string('whatsapp_phone', 20)->nullable()->after('whatsapp_country_code');
            $table->timestamp('whatsapp_verified_at')->nullable()->after('whatsapp_phone');
        });
    }

    public function down(): void
    {
        Schema::table('dua_submissions', function (Blueprint $table) {
            $table->dropColumn([
                'whatsapp_country_code',
                'whatsapp_phone',
                'whatsapp_verified_at',
            ]);
        });
    }
};
