<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('download_statistics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('package_id')->constrained()->cascadeOnDelete();
            $table->foreignId('version_id')->nullable()->constrained()->nullOnDelete();
            $table->date('date')->index();
            $table->unsignedInteger('downloads')->default(0);
            $table->unique(['package_id', 'version_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('download_statistics');
    }
};
