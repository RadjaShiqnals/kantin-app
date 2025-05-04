<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('menu', function (Blueprint $table) {
            $table->id();
            $table->string('nama_makanan', 100);
            $table->double('harga');
            $table->enum('jenis', ['makanan', 'minuman']);
            $table->string('foto', 255)->nullable();
            $table->text('deskripsi')->nullable();
            $table->foreignId('id_stan')->constrained('stan')->onDelete('cascade');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('menu');
    }
};
