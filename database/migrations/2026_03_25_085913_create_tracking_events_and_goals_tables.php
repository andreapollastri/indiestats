<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tracking_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('site_id')->constrained()->cascadeOnDelete();
            $table->string('visitor_id', 64)->index();
            $table->string('name', 128)->index();
            $table->string('path', 2048)->nullable();
            $table->json('properties')->nullable();
            $table->timestamp('created_at')->index();

            $table->index(['site_id', 'created_at']);
            $table->index(['site_id', 'name', 'created_at']);
        });

        Schema::create('goals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('site_id')->constrained()->cascadeOnDelete();
            $table->string('label', 255);
            $table->string('event_name', 128);
            $table->timestamps();

            $table->unique(['site_id', 'event_name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('goals');
        Schema::dropIfExists('tracking_events');
    }
};
