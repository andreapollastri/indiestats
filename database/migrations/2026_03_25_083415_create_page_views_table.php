<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('page_views', function (Blueprint $table) {
            $table->id();
            $table->foreignId('site_id')->constrained()->cascadeOnDelete();
            $table->string('visitor_id', 64)->index();
            $table->string('path', 2048);
            $table->text('referrer_url')->nullable();
            $table->string('referrer_source', 64)->index();
            $table->string('utm_source', 255)->nullable();
            $table->string('utm_medium', 255)->nullable();
            $table->string('utm_campaign', 255)->nullable();
            $table->string('utm_term', 255)->nullable();
            $table->string('utm_content', 255)->nullable();
            $table->string('search_query', 512)->nullable();
            $table->string('browser', 64)->nullable();
            $table->string('os', 64)->nullable();
            $table->string('device_type', 32)->nullable();
            $table->char('country_code', 2)->nullable()->index();
            $table->unsignedInteger('duration_seconds')->nullable();
            $table->timestamp('created_at')->index();

            $table->index(['site_id', 'created_at']);
            $table->index(['site_id', 'visitor_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('page_views');
    }
};
