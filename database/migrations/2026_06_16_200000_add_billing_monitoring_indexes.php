<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('billing_purchases', function (Blueprint $table) {
            $table->index(['user_id', 'created_at'], 'billing_purchases_user_created_index');
        });

        Schema::table('billing_purchase_events', function (Blueprint $table) {
            $table->index('stripe_event_id', 'billing_purchase_events_stripe_event_id_index');
            $table->index('created_at', 'billing_purchase_events_created_at_index');
        });
    }

    public function down(): void
    {
        Schema::table('billing_purchases', function (Blueprint $table) {
            $table->dropIndex('billing_purchases_user_created_index');
        });

        Schema::table('billing_purchase_events', function (Blueprint $table) {
            $table->dropIndex('billing_purchase_events_stripe_event_id_index');
            $table->dropIndex('billing_purchase_events_created_at_index');
        });
    }
};
