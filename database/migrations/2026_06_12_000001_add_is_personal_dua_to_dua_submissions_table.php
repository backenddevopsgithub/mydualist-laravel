<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dua_submissions', function (Blueprint $table) {
            $table->boolean('is_personal_dua')->default(false)->after('is_anonymous');
            $table->index(['dua_list_id', 'is_personal_dua']);
        });
    }

    public function down(): void
    {
        Schema::table('dua_submissions', function (Blueprint $table) {
            $table->dropIndex(['dua_list_id', 'is_personal_dua']);
            $table->dropColumn('is_personal_dua');
        });
    }
};
