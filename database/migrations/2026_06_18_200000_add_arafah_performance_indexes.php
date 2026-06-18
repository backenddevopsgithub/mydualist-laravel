<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dua_submissions', function (Blueprint $table): void {
            $table->index('reported_at', 'dua_submissions_reported_at_index');
            $table->index(
                ['dua_list_id', 'is_personal_dua', 'id'],
                'dua_submissions_list_personal_id_index',
            );
            $table->index(
                ['dua_list_id', 'is_personal_dua', 'is_locked', 'unlocked_at'],
                'dua_submissions_list_personal_lock_visibility_index',
            );
            $table->index(
                ['dua_list_id', 'is_personal_dua', 'digest_sent_at', 'status'],
                'dua_submissions_list_digest_pending_index',
            );
        });

        Schema::table('dua_lists', function (Blueprint $table): void {
            $table->index(
                ['email_frequency', 'status'],
                'dua_lists_email_frequency_status_index',
            );
            $table->index(
                ['status', 'published_at'],
                'dua_lists_status_published_at_index',
            );
        });
    }

    public function down(): void
    {
        Schema::table('dua_submissions', function (Blueprint $table): void {
            $table->dropIndex('dua_submissions_reported_at_index');
            $table->dropIndex('dua_submissions_list_personal_id_index');
            $table->dropIndex('dua_submissions_list_personal_lock_visibility_index');
            $table->dropIndex('dua_submissions_list_digest_pending_index');
        });

        Schema::table('dua_lists', function (Blueprint $table): void {
            $table->dropIndex('dua_lists_email_frequency_status_index');
            $table->dropIndex('dua_lists_status_published_at_index');
        });
    }
};
