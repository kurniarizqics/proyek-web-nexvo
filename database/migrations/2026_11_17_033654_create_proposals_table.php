<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('proposals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade'); // Koordiv yang mengajukan dana
            $table->string('title'); // Judul pengajuan anggaran
            $table->decimal('amount', 15, 2); // Nominal dana yang diajukan
            $table->text('reason'); // Alasan atau rincian keperluan
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending'); // Status persetujuan Ketua/Bendahara
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('proposals');
    }
};