<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('tasks', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('file_name');
            $table->string('stored_path')->nullable();
            $table->string('output_path')->nullable();
            $table->bigInteger('file_size')->default(0);
            $table->string('status')->default('idle'); // idle, uploaded, scanning, scanned, processing, completed, error
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('generated_pdfs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('file_name');
            $table->string('stored_path');
            $table->bigInteger('file_size')->default(0);
            $table->string('task_id')->nullable();
            $table->timestamps();
        });

        Schema::create('storage_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->string('event_type'); // upload, cleanup, error
            $table->string('message');
            $table->bigInteger('size_delta')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('storage_logs');
        Schema::dropIfExists('generated_pdfs');
        Schema::dropIfExists('tasks');
    }
};
