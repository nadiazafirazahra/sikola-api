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
        Schema::create('t_spkls', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_spkl');
            $table->string('type');
            $table->string('category');
            $table->string('category_detail');
            $table->text('note')->nullable();
            $table->boolean('is_late');
            $table->boolean('kolektif');
            $table->boolean('is_print');
            $table->string('npk_1');
            $table->string('npk_2')->nullable();
            $table->string('npk_3')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('t_spkls');
    }
};
