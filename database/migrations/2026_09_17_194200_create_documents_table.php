<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Dokumente sind eigene Datensätze, keine Felder der Bewerbung. Dadurch lassen
 * sich mehrere Fassungen je Bewerbung halten, und ein erneutes Erzeugen des
 * Anschreibens überschreibt nicht den Lebenslauf.
 *
 * `content` ist der Snapshot: was für genau diese Bewerbung erzeugt und
 * bearbeitet wurde. `html_path`/`pdf_path` zeigen auf die gerenderten Dateien.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('application_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('generator_session_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type');
            $table->string('language');
            $table->string('layout')->nullable();
            $table->json('content');
            $table->unsignedInteger('version')->default(1);
            $table->string('html_path')->nullable();
            $table->string('pdf_path')->nullable();
            $table->timestamp('rendered_at')->nullable();
            $table->timestamps();

            $table->index(['application_id', 'type', 'version']);
            $table->index(['user_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};
