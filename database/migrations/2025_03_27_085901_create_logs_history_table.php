<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up() {
        Schema::create('logs_history', function (Blueprint $table) {
            $table->id();
           
            $table->unsignedBigInteger('doc_id'); // Reference to document
            $table->string('action'); // Action performed
            $table->integer('status_update'); // Status change
            $table->timestamps(); // created_at and updated_at timestamps

            // Foreign keys (optional: add if needed)
            $table->foreign('log_id')->references('id')->on('logs')->onDelete('cascade');
            $table->foreign('doc_id')->references('id')->on('documents')->onDelete('cascade');
        });
    }

    public function down() {
        Schema::dropIfExists('logs_history');
    }
};