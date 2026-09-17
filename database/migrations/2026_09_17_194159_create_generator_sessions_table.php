<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Der Arbeitsplatz einer einzelnen Stelle: die erfasste Anzeige, die Felder,
 * die Einstellungen und die Stellen-Übersicht. Er liegt auf dem Server, damit
 * ein Neuladen dort bleibt, wo der Nutzer war, und damit die Uhr für die
 * Recherchezeit unabhängig vom Browser läuft.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('generator_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('queue_item_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('application_id')->nullable()->constrained()->nullOnDelete();
            $table->string('job_url')->nullable();
            $table->longText('job_posting')->nullable();
            $table->string('company')->default('');
            $table->string('position')->default('');
            $table->string('contact_person')->default('');
            $table->string('city')->default('');
            $table->text('company_address')->nullable();
            $table->string('language')->default('de');
            $table->string('layout')->default('modern');
            $table->string('scope')->default('both');
            $table->text('notes')->nullable();
            $table->json('summary')->nullable();
            $table->timestamp('timer_started_at')->nullable();
            $table->unsignedInteger('pending_research_seconds')->default(0);
            $table->timestamps();

            $table->index(['user_id', 'updated_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('generator_sessions');
    }
};
