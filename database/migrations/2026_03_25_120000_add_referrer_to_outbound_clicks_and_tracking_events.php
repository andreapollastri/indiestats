<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('outbound_clicks', function (Blueprint $table) {
            $table->text('referrer_url')->nullable()->after('target_url');
            $table->string('referrer_source', 64)->nullable()->after('referrer_url');
            $table->index(['site_id', 'referrer_source']);
        });

        Schema::table('tracking_events', function (Blueprint $table) {
            $table->text('referrer_url')->nullable()->after('path');
            $table->string('referrer_source', 64)->nullable()->after('referrer_url');
            $table->index(['site_id', 'referrer_source']);
        });
    }

    public function down(): void
    {
        Schema::table('tracking_events', function (Blueprint $table) {
            $table->dropIndex(['site_id', 'referrer_source']);
            $table->dropColumn(['referrer_url', 'referrer_source']);
        });

        Schema::table('outbound_clicks', function (Blueprint $table) {
            $table->dropIndex(['site_id', 'referrer_source']);
            $table->dropColumn(['referrer_url', 'referrer_source']);
        });
    }
};
