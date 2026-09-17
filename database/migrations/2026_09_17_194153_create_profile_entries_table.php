<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Berufserfahrung, Ausbildung, Hard/Soft Skills und Sprachen in einer Tabelle.
 *
 * Sprachneutrale Fakten (Firma, Zeitraum, Sichtbarkeit, Reihenfolge) stehen als
 * Spalten; alles, was ein Leser als Text sieht, liegt in `translations` je
 * Sprache — vom Nutzer geschrieben, nie maschinell übersetzt.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('profile_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('section');
            $table->string('organization')->default('');
            $table->string('start_month', 7)->nullable();
            $table->string('end_month', 7)->nullable();
            $table->boolean('is_current')->default(false);
            $table->boolean('is_visible')->default(true);
            $table->unsignedInteger('position')->default(0);
            $table->json('translations');
            $table->string('legacy_id')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'section']);
            $table->unique(['user_id', 'legacy_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('profile_entries');
    }
};
