<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role', 20)->default('base');
        });

        DB::table('users')->update(['role' => 'admin']);

        Schema::create('site_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('site_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['site_id', 'user_id']);
        });

        foreach (DB::table('sites')->orderBy('id')->cursor() as $site) {
            DB::table('site_user')->insert([
                'site_id' => $site->id,
                'user_id' => $site->user_id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('site_user');

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('role');
        });
    }
};
