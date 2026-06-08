<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('proxy_upstreams', function (Blueprint $table) {
            $table->id();
            $table->boolean('enabled')->default(true);
            $table->string('name');
            $table->string('upstream_url');
            $table->string('auth_type')->default('none');
            $table->string('auth_username')->nullable();
            $table->text('auth_password')->nullable();
            $table->text('auth_token')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::table('proxy_settings', function (Blueprint $table) {
            $table->dropColumn([
                'upstream_url',
                'upstream_auth_type',
                'upstream_auth_username',
                'upstream_auth_password',
                'upstream_auth_token',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('proxy_upstreams');

        Schema::table('proxy_settings', function (Blueprint $table) {
            $table->string('upstream_url')->default('https://repo.packagist.org');
            $table->string('upstream_auth_type')->default('none');
            $table->string('upstream_auth_username')->nullable();
            $table->text('upstream_auth_password')->nullable();
            $table->text('upstream_auth_token')->nullable();
        });
    }
};
