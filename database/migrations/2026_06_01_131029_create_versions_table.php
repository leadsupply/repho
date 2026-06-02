<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('package_id')->constrained()->cascadeOnDelete();
            $table->string('version');
            $table->string('version_normalized');
            $table->string('reference');
            $table->json('composer_json');
            $table->timestamp('released_at')->nullable();
            $table->timestamps();

            $table->unique(['package_id', 'version_normalized']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('versions');
    }
};
