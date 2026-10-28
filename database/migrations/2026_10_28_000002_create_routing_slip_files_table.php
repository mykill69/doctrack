<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Every uploaded file of a routing slip is stored here so that a single
     * routing slip transaction can have more than one attachment. The
     * routing_slip.document column is kept as the primary file for backwards
     * compatibility with the existing print/view flows.
     */
    public function up()
    {
        if (Schema::hasTable('routing_slip_files')) {
            return;
        }

        Schema::create('routing_slip_files', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('routing_slip_id')->index();
            $table->string('rslip_id')->index();
            $table->string('file_name');
            $table->string('original_name');
            $table->unsignedBigInteger('file_size')->default(0);
            $table->unsignedBigInteger('uploaded_by')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('routing_slip_files');
    }
};
