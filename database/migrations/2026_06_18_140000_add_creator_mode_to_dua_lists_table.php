<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dua_lists', function (Blueprint $table) {
            $table->string('list_mode')->nullable()->after('cover_image_path');
            $table->string('donation_link')->nullable()->after('list_mode');
            $table->text('donation_note')->nullable()->after('donation_link');
            $table->unsignedInteger('insights_views')->default(0)->after('donation_note');
            $table->unsignedInteger('insights_clicks')->default(0)->after('insights_views');
        });
    }

    public function down(): void
    {
        Schema::table('dua_lists', function (Blueprint $table) {
            $table->dropColumn([
                'list_mode',
                'donation_link',
                'donation_note',
                'insights_views',
                'insights_clicks',
            ]);
        });
    }
};
