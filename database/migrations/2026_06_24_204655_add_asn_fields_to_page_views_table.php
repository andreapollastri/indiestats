<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('page_views', function (Blueprint $table) {
            $table->unsignedInteger('asn')->nullable()->after('country_code');
            $table->string('as_organization', 255)->nullable()->after('asn');
            $table->index('asn');
        });
    }

    public function down(): void
    {
        Schema::table('page_views', function (Blueprint $table) {
            $table->dropIndex(['asn']);
            $table->dropColumn(['asn', 'as_organization']);
        });
    }
};
