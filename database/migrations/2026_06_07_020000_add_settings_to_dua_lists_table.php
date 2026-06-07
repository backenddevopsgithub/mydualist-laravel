<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dua_lists', function (Blueprint $table) {
            $table->unsignedTinyInteger('dua_limit_per_person')->nullable()->after('cover_image_path');
            $table->string('display_order')->default('date')->after('dua_limit_per_person');
            $table->string('email_frequency')->default('every_submission')->after('display_order');
        });
    }

    public function down(): void
    {
        Schema::table('dua_lists', function (Blueprint $table) {
            $table->dropColumn(['dua_limit_per_person', 'display_order', 'email_frequency']);
        });
    }
};
