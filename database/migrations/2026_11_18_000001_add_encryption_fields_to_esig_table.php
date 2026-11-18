<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * is_encrypted : 1 = the file in storage/app/esignature is encrypted (Crypt / APP_KEY)
     *                0 = legacy plain image uploaded before encryption -> user must re-upload
     * mime_type    : real image type of the decrypted signature (image/png, image/jpeg)
     */
    public function up()
    {
        Schema::table('esig', function (Blueprint $table) {
            if (!Schema::hasColumn('esig', 'is_encrypted')) {
                $table->boolean('is_encrypted')->default(false)->after('esig_file');
            }
            if (!Schema::hasColumn('esig', 'mime_type')) {
                $table->string('mime_type', 50)->nullable()->after('is_encrypted');
            }
        });
    }

    public function down()
    {
        Schema::table('esig', function (Blueprint $table) {
            if (Schema::hasColumn('esig', 'mime_type')) {
                $table->dropColumn('mime_type');
            }
            if (Schema::hasColumn('esig', 'is_encrypted')) {
                $table->dropColumn('is_encrypted');
            }
        });
    }
};
