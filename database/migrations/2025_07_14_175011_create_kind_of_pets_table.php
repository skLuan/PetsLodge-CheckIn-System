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
        Schema::create('kind_of_pets', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique(); // e.g., 'dog', 'cat', 'bird'
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('kind_of_pets');
    }
};
