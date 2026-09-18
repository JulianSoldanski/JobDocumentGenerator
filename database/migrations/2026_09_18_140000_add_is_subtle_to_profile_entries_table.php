<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Manche Stationen gehören in den Lebenslauf, sollen aber nicht ins Auge
 * springen — ein Nebenjob etwa. Ihr Titel erscheint dann leiser.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('profile_entries', function (Blueprint $table) {
            $table->boolean('is_subtle')->default(false)->after('is_visible');
        });
    }

    public function down(): void
    {
        Schema::table('profile_entries', function (Blueprint $table) {
            $table->dropColumn('is_subtle');
        });
    }
};
