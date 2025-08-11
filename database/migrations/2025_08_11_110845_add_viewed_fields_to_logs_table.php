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
    Schema::table('logs', function (Blueprint $table) {
        $table->integer('viewed_status')->nullable()->default(null)->after('status_update');
        $table->timestamp('viewed_at')->nullable()->after('viewed_status');
    });
}

public function down()
{
    Schema::table('logs', function (Blueprint $table) {
        $table->dropColumn(['viewed_status', 'viewed_at']);
    });
}

};
