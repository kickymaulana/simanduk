<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('merk', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kualitas_id')->constrained('kualitas')->restrictOnDelete();
            $table->string('merk');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['kualitas_id', 'merk']);
        });

        Schema::create('gudang', function (Blueprint $table) {
            $table->id();
            $table->string('gudang')->unique();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('aksesori', function (Blueprint $table) {
            $table->id();
            $table->string('aksesori')->unique();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('packing', function (Blueprint $table) {
            $table->id();
            $table->string('qrcode_kardus')->unique();
            $table->enum('jenis', ['Body', 'Tangki']);
            $table->foreignId('kualitas_id')->constrained('kualitas')->restrictOnDelete();
            $table->foreignId('merk_id')->constrained('merk')->restrictOnDelete();
            $table->foreignId('warna_id')->constrained('warna')->restrictOnDelete();
            $table->foreignId('gudang_id')->constrained('gudang')->restrictOnDelete();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->timestamps();
        });

        Schema::create('packing_accessories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('packing_id')->constrained('packing')->cascadeOnDelete();
            $table->foreignId('aksesori_id')->constrained('aksesori')->restrictOnDelete();
            $table->unsignedInteger('quantity');
            $table->timestamps();
            $table->unique(['packing_id', 'aksesori_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('packing_accessories');
        Schema::dropIfExists('packing');
        Schema::dropIfExists('aksesori');
        Schema::dropIfExists('gudang');
        Schema::dropIfExists('merk');
    }
};
