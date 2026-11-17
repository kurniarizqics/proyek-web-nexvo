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
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained('categories')->onDelete('cascade'); // Relasi ke kategori kas
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade'); // Bendahara/Admin yang menginput
            $table->decimal('amount', 15, 2); // Nominal transaksi
            $table->date('transaction_date'); // Tanggal transaksi
            $table->text('description')->nullable(); // Keterangan transaksi
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};