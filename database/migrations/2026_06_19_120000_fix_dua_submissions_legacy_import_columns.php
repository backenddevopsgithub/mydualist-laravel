<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dua_submissions', function (Blueprint $table) {
            $table->dropUnique(['wp_post_id']);
        });

        Schema::table('dua_submissions', function (Blueprint $table) {
            $table->bigInteger('wp_post_id')->nullable()->unique()->change();
            $table->string('whatsapp_phone', 30)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('dua_submissions', function (Blueprint $table) {
            $table->dropUnique(['wp_post_id']);
        });

        Schema::table('dua_submissions', function (Blueprint $table) {
            $table->unsignedBigInteger('wp_post_id')->nullable()->unique()->change();
            $table->string('whatsapp_phone', 20)->nullable()->change();
        });
    }
};
