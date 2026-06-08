<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('proxy_settings', function (Blueprint $table) {
            $table->id();
            $table->boolean('enabled')->default(false);
            $table->string('upstream_url')->default('https://repo.packagist.org');
            $table->string('auth_type')->default('none');
            $table->string('auth_username')->nullable();
            $table->text('auth_password')->nullable();
            $table->text('auth_token')->nullable();
            $table->string('upstream_auth_type')->default('none');
            $table->string('upstream_auth_username')->nullable();
            $table->text('upstream_auth_password')->nullable();
            $table->text('upstream_auth_token')->nullable();
            $table->unsignedInteger('metadata_cache_ttl')->default(3600);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('proxy_settings');
    }
};
