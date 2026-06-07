<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dua_submissions', function (Blueprint $table) {
            $table->boolean('is_anonymous')->default(false)->after('email');
            $table->text('note')->nullable()->after('content');
            $table->timestamp('hidden_at')->nullable()->after('completed_at');
            $table->timestamp('archived_at')->nullable()->after('hidden_at');
            $table->timestamp('reported_at')->nullable()->after('archived_at');
            $table->softDeletes();

            $table->index(['dua_list_id', 'email', 'created_at']);
            $table->index(['dua_list_id', 'status', 'created_at']);
            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::table('dua_submissions', function (Blueprint $table) {
            $table->dropIndex(['dua_list_id', 'email', 'created_at']);
            $table->dropIndex(['dua_list_id', 'status', 'created_at']);
            $table->dropIndex(['status', 'created_at']);
            $table->dropSoftDeletes();
            $table->dropColumn(['is_anonymous', 'note', 'hidden_at', 'archived_at', 'reported_at']);
        });
    }
};
