<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('shadow_experiments', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->unique();
            $table->string('status')->default('running');
            $table->string('version')->nullable();
            $table->decimal('sample_percentage', 5, 2)->default(1);
            $table->unsignedTinyInteger('sandbox_level')->default(1);
            $table->timestamps();
        });

        Schema::create('shadow_runs', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('experiment')->index();
            $table->string('version')->index();
            $table->char('subject_hash', 64)->nullable()->index();
            $table->string('correlation_id')->nullable()->index();
            $table->json('tags')->nullable();
            $table->string('primary_status', 32);
            $table->string('candidate_status', 32)->nullable();
            $table->boolean('matched')->nullable()->index();
            $table->string('difference_type', 64)->nullable()->index();
            $table->char('difference_fingerprint', 64)->nullable()->index();
            $table->unsignedBigInteger('primary_duration_us')->default(0);
            $table->unsignedBigInteger('candidate_duration_us')->nullable();
            $table->unsignedBigInteger('primary_memory_bytes')->default(0);
            $table->unsignedBigInteger('candidate_memory_bytes')->nullable();
            $table->unsignedInteger('primary_query_count')->default(0);
            $table->unsignedInteger('candidate_query_count')->nullable();
            $table->unsignedTinyInteger('sandbox_level')->default(1);
            $table->timestamp('created_at')->useCurrent()->index();
        });

        Schema::create('shadow_effects', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->ulid('run_id')->index();
            $table->string('role', 16);
            $table->string('type', 32)->index();
            $table->string('operation', 64);
            $table->text('resource')->nullable();
            $table->boolean('blocked')->default(false);
            $table->boolean('simulated')->default(false);
            $table->json('payload')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->foreign('run_id')->references('id')->on('shadow_runs')->cascadeOnDelete();
        });

        Schema::create('shadow_payloads', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->ulid('run_id')->unique();
            $table->longText('primary_payload')->nullable();
            $table->longText('candidate_payload')->nullable();
            $table->longText('diff_payload')->nullable();
            $table->timestamp('expires_at')->nullable()->index();
            $table->timestamp('created_at')->useCurrent();
            $table->foreign('run_id')->references('id')->on('shadow_runs')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shadow_payloads');
        Schema::dropIfExists('shadow_effects');
        Schema::dropIfExists('shadow_runs');
        Schema::dropIfExists('shadow_experiments');
    }
};
