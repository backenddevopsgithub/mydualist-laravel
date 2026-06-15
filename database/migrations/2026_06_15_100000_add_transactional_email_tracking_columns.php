<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('welcome_email_sent_at')->nullable()->after('email_verified_at');
        });

        Schema::table('dua_lists', function (Blueprint $table) {
            $table->timestamp('list_created_email_sent_at')->nullable()->after('published_at');
            $table->timestamp('submission_quota_warning_sent_at')->nullable()->after('list_created_email_sent_at');
        });

        Schema::table('dua_submissions', function (Blueprint $table) {
            $table->timestamp('completion_notified_at')->nullable()->after('completed_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('welcome_email_sent_at');
        });

        Schema::table('dua_lists', function (Blueprint $table) {
            $table->dropColumn(['list_created_email_sent_at', 'submission_quota_warning_sent_at']);
        });

        Schema::table('dua_submissions', function (Blueprint $table) {
            $table->dropColumn('completion_notified_at');
        });
    }
};
