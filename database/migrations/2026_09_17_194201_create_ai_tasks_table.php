<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Jeder KI-Aufruf ist ein Datensatz, der im Hintergrund abgearbeitet wird. Der
 * Browser wartet nicht auf drei hintereinander geschaltete Modellantworten,
 * sondern fragt den Stand ab — Teilergebnisse erscheinen, sobald sie da sind.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_tasks', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('type');
            $table->nullableMorphs('subject');
            $table->string('status')->default('queued');
            $table->json('input');
            $table->json('result')->nullable();
            $table->text('error')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'type', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_tasks');
    }
};
