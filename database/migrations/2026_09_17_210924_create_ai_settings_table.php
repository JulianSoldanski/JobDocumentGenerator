<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Der KI-Zugang gehört zum Konto, nicht zur Serverkonfiguration: Jeder Nutzer
 * hinterlegt seinen eigenen Schlüssel und zahlt damit seine eigenen Aufrufe.
 *
 * Der Schlüssel liegt verschlüsselt in der Datenbank und verlässt den Server
 * nie wieder — die Oberfläche bekommt nur zu sehen, ob einer hinterlegt ist.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('provider')->nullable();
            $table->string('model')->nullable();
            $table->text('api_key')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_settings');
    }
};
