<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Projekte in zwei Detailtiefen: kurz für den Lebenslauf (Titel,
 * Zusammenfassung), ausführlich für die Projektliste (Rolle, Ausgangslage,
 * Beitrag, Ergebnis). Beides liegt je Sprache in `translations`.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('position')->default(0);
            $table->boolean('is_visible')->default(true);
            $table->boolean('in_project_list')->default(true);
            $table->string('link')->nullable();
            $table->string('grade')->nullable();
            $table->json('tags');
            $table->string('client')->default('');
            $table->string('period')->default('');
            $table->string('team_size')->default('');
            $table->json('technologies');
            $table->json('translations');
            $table->string('legacy_id')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'position']);
            $table->unique(['user_id', 'legacy_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};
