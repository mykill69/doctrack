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
        Schema::create('assign_logs', function (Blueprint $table) {
            $table->id(); // Auto-incrementing ID
            $table->unsignedBigInteger('new_user'); // ID of the user who made the assignment
            $table->unsignedBigInteger('doc_id'); // ID of the document
            $table->unsignedBigInteger('route_id'); // ID of the route
            $table->string('assn_code')->nullable(); // Assignment code, can be null
            $table->unsignedBigInteger('assigned_to'); // User ID of the person assigned to
            $table->timestamps(); // Automatically manages created_at and updated_at

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('assign_logs');
    }
};
