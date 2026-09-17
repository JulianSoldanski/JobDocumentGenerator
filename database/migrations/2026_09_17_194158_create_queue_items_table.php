<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Die Sammelstelle für Stellen, die noch nicht bearbeitet sind. Die URL ist
 * beim Speichern bereits von Tracking-Parametern befreit; der eindeutige Index
 * sorgt dafür, dass dieselbe Stelle aus drei Quellen nur einmal in der Liste
 * landet.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('queue_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('url', 500);
            $table->string('title')->default('');
            $table->string('note', 500)->default('');
            $table->string('status')->default('open');
            $table->timestamp('processed_at')->nullable();
            $table->foreignId('application_id')->nullable()->constrained()->nullOnDelete();
            $table->string('legacy_id')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'url']);
            $table->unique(['user_id', 'legacy_id']);
            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('queue_items');
    }
};
