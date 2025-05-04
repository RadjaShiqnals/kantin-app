<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stan', function (Blueprint $table) {
            $table->id();
            $table->string('nama_stan', 100);
            $table->string('nama_pemilik', 100);
            $table->string('telp', 20);
            $table->foreignId('id_user')->constrained('users')->onDelete('cascade');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stan');
    }
};