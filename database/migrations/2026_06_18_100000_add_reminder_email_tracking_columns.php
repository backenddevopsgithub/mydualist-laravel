<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dua_lists', function (Blueprint $table) {
            $table->timestamp('no_activity_reminder_sent_at')->nullable()->after('submission_quota_warning_sent_at');
            $table->timestamp('closing_soon_reminder_sent_at')->nullable()->after('no_activity_reminder_sent_at');
            $table->timestamp('list_image_reminder_sent_at')->nullable()->after('closing_soon_reminder_sent_at');
        });
    }

    public function down(): void
    {
        Schema::table('dua_lists', function (Blueprint $table) {
            $table->dropColumn([
                'no_activity_reminder_sent_at',
                'closing_soon_reminder_sent_at',
                'list_image_reminder_sent_at',
            ]);
        });
    }
};
