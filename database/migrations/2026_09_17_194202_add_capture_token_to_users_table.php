<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Das Bookmarklet ruft eine Adresse mit persönlichem Token auf. So funktioniert
 * es unabhängig von Cookies und Cross-Site-Regeln — und ohne Browser-Add-on.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('capture_token', 64)->nullable()->unique()->after('password');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('capture_token');
        });
    }
};
