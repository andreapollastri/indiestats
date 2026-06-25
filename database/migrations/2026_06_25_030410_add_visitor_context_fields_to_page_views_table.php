<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('page_views', function (Blueprint $table) {
            $table->string('page_title', 512)->nullable()->after('path');
            $table->string('page_query', 2048)->nullable()->after('page_title');
            $table->string('gclid', 255)->nullable()->after('utm_content');
            $table->string('fbclid', 255)->nullable()->after('gclid');
            $table->string('msclkid', 255)->nullable()->after('fbclid');
            $table->string('browser_language', 16)->nullable()->after('device_type');
            $table->string('timezone', 64)->nullable()->after('browser_language');
            $table->string('session_id', 64)->nullable()->after('visitor_id')->index();
            $table->string('browser_version', 32)->nullable()->after('browser');
            $table->boolean('is_bot')->default(false)->after('browser_version');
        });
    }

    public function down(): void
    {
        Schema::table('page_views', function (Blueprint $table) {
            $table->dropIndex(['session_id']);
            $table->dropColumn([
                'page_title',
                'page_query',
                'gclid',
                'fbclid',
                'msclkid',
                'browser_language',
                'timezone',
                'session_id',
                'browser_version',
                'is_bot',
            ]);
        });
    }
};
