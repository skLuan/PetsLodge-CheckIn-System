<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('pets', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->date('birth_date')->nullable();
            $table->string('race')->nullable();
            $table->string('color')->nullable();
            $table->text('health_conditions')->nullable();
            $table->text('warnings')->nullable();
            $table->foreignId('gender_id')->constrained()->onDelete('restrict');
            $table->foreignId('kind_of_pet_id')->constrained()->onDelete('restrict');
            $table->foreignId('castrated_id')->constrained()->onDelete('restrict');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('pets');
    }
};
