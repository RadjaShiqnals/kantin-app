<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('menu_diskon', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_menu')->constrained('menu')->onDelete('cascade');
            $table->foreignId('id_diskon')->constrained('diskon')->onDelete('cascade');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('menu_diskon');
    }
};