<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dua_lists', function (Blueprint $table) {
            $table->unsignedBigInteger('wp_post_id')->nullable()->unique()->after('id');
        });

        Schema::table('dua_submissions', function (Blueprint $table) {
            $table->unsignedBigInteger('wp_post_id')->nullable()->unique()->after('id');
        });

        Schema::table('community_duas', function (Blueprint $table) {
            $table->unsignedBigInteger('wp_post_id')->nullable()->unique()->after('id');
        });

        Schema::table('dua_suggestions', function (Blueprint $table) {
            $table->unsignedBigInteger('wp_post_id')->nullable()->unique()->after('id');
        });

        Schema::table('billing_purchases', function (Blueprint $table) {
            $table->unsignedBigInteger('wp_order_id')->nullable()->unique()->after('id');
        });
    }

    public function down(): void
    {
        Schema::table('billing_purchases', function (Blueprint $table) {
            $table->dropUnique(['wp_order_id']);
            $table->dropColumn('wp_order_id');
        });

        Schema::table('dua_suggestions', function (Blueprint $table) {
            $table->dropUnique(['wp_post_id']);
            $table->dropColumn('wp_post_id');
        });

        Schema::table('community_duas', function (Blueprint $table) {
            $table->dropUnique(['wp_post_id']);
            $table->dropColumn('wp_post_id');
        });

        Schema::table('dua_submissions', function (Blueprint $table) {
            $table->dropUnique(['wp_post_id']);
            $table->dropColumn('wp_post_id');
        });

        Schema::table('dua_lists', function (Blueprint $table) {
            $table->dropUnique(['wp_post_id']);
            $table->dropColumn('wp_post_id');
        });
    }
};
