<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('diskon', function (Blueprint $table) {
            $table->id();
            $table->string('nama_diskon', 100);
            $table->double('persentase_diskon');
            $table->dateTime('tanggal_awal');
            $table->dateTime('tanggal_akhir');
            $table->foreignId('id_stan')->constrained('stan')->onDelete('cascade');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('diskon');
    }
};