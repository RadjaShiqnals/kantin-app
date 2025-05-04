<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transaksi', function (Blueprint $table) {
            $table->id();
            $table->dateTime('tanggal');
            $table->foreignId('id_stan')->constrained('stan')->onDelete('cascade');
            $table->foreignId('id_siswa')->constrained('siswa')->onDelete('cascade');
            $table->enum('status', ['belum dikonfirm', 'dimasak', 'diantar', 'sampai']);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transaksi');
    }
};
