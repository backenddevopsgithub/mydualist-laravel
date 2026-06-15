<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dua_submissions', function (Blueprint $table) {
            $table->timestamp('digest_sent_at')->nullable()->after('completion_notified_at');
            $table->index(['dua_list_id', 'digest_sent_at']);
        });
    }

    public function down(): void
    {
        Schema::table('dua_submissions', function (Blueprint $table) {
            $table->dropIndex(['dua_list_id', 'digest_sent_at']);
            $table->dropColumn('digest_sent_at');
        });
    }
};
