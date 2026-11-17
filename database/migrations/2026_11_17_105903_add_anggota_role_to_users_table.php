<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('ketua', 'bendahara', 'koordiv', 'anggota', 'admin') NOT NULL DEFAULT 'anggota'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('ketua', 'bendahara', 'koordiv', 'admin') NOT NULL DEFAULT 'koordiv'");
    }
};