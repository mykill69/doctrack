<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * date_received : date the document was received (shown under RECEIVED BY/DATE)
     * source_id     : user selected as the Source of the transmittal
     *                 (user_id stays the creator / encoder of the transaction)
     */
    public function up()
    {
        Schema::table('doctrack_slip', function (Blueprint $table) {
            if (!Schema::hasColumn('doctrack_slip', 'date_received')) {
                $table->date('date_received')->nullable()->after('ctrl_no');
            }
            if (!Schema::hasColumn('doctrack_slip', 'source_id')) {
                $table->unsignedBigInteger('source_id')->nullable()->after('date_received');
                $table->index('source_id', 'doctrack_slip_source_id_idx');
            }
        });
    }

    public function down()
    {
        Schema::table('doctrack_slip', function (Blueprint $table) {
            if (Schema::hasColumn('doctrack_slip', 'source_id')) {
                $table->dropIndex('doctrack_slip_source_id_idx');
                $table->dropColumn('source_id');
            }
            if (Schema::hasColumn('doctrack_slip', 'date_received')) {
                $table->dropColumn('date_received');
            }
        });
    }
};
