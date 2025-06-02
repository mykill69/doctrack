<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAssignLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('assign_logs', function (Blueprint $table) {
            $table->id(); // Primary key (auto-incrementing)
            $table->unsignedBigInteger('new_user'); // Foreign key for user who was assigned
            $table->unsignedBigInteger('doc_id'); // Foreign key for document
            $table->unsignedBigInteger('route_id'); // Foreign key for route
            $table->integer('assn_code')->nullable(); // Assignment code (can be null)
            $table->string('assigned_to')->nullable(); // The user/department to which the document is assigned
            $table->timestamps(); // Includes created_at and updated_at columns

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
}
