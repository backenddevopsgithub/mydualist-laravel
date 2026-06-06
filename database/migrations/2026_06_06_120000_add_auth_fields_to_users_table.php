<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role', 20)->default('user')->after('password');
            $table->string('status', 20)->default('active')->after('role');
            $table->string('avatar')->nullable()->after('status');
            $table->unsignedBigInteger('wp_legacy_id')->nullable()->after('avatar');
            $table->text('wp_password_hash')->nullable()->after('wp_legacy_id');

            $table->index('role');
            $table->index('status');
            $table->unique('wp_legacy_id');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['wp_legacy_id']);
            $table->dropIndex(['role']);
            $table->dropIndex(['status']);
            $table->dropColumn([
                'role',
                'status',
                'avatar',
                'wp_legacy_id',
                'wp_password_hash',
            ]);
        });
    }
};
