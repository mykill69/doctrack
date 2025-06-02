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
        Schema::create('routing_slip', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('rslip_id'); // Foreign key for transaction
            $table->unsignedBigInteger('user_id'); // Foreign key for user
            $table->string('trans_remarks'); // Transaction remarks
            $table->string('other_remarks')->nullable(); // Other remarks (optional)
            $table->string('r_destination'); // Transaction remarks
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
        Schema::dropIfExists('routing_slip');
    }
};
