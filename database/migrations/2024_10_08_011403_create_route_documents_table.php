<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('route_documents', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('route_id'); // Link to documents table
            $table->string('destination_1')->nullable();
            $table->string('destination_2')->nullable();
            $table->string('destination_3')->nullable();
            $table->string('destination_4')->nullable();
            $table->string('destination_5')->nullable();
            $table->string('destination_6')->nullable();
            $table->string('destination_7')->nullable();
            $table->string('destination_8')->nullable();
            $table->string('destination_9')->nullable();
            $table->string('destination_10')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('route_documents');
    }
};
