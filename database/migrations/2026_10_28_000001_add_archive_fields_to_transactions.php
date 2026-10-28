<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add the archival fields used by the Archive Logbook.
     *
     * The Doctrack system stores one row per action, so a "transaction" is
     * identified by routing_slip.rslip_id or doctrack_slip.docslip_id. Every
     * row of the transaction receives the archival fields, which keeps the
     * history complete while allowing the normal pages to filter it out.
     */
    public function up()
    {
        if (!Schema::hasTable('archive_logs')) {
            Schema::create('archive_logs', function (Blueprint $table) {
                $table->id();
                $table->unsignedSmallInteger('archive_year');
                $table->string('transaction_type', 40);
                $table->unsignedBigInteger('records_count')->default(0);
                $table->unsignedBigInteger('archived_by')->nullable();
                $table->timestamp('archived_at')->nullable();
                $table->string('status', 20)->default('processing');
                $table->text('message')->nullable();
                $table->timestamps();

                $table->index('archived_at', 'archive_logs_archived_at_idx');
                $table->index(['archive_year', 'transaction_type'], 'archive_logs_year_type_idx');
            });
        }

        $this->addArchiveColumns('routing_slip');
        $this->addArchiveColumns('doctrack_slip');

        // Indexes used by the preview / archive grouping and by the normal
        // page filters (the archived rows are skipped with is_archived = 0).
        $this->addIndex('routing_slip', ['is_archived', 'created_at'], 'routing_slip_archived_created_idx');
        $this->addIndex('routing_slip', ['is_archived', 'rslip_id'], 'routing_slip_archived_rslip_idx');
        $this->addIndex('routing_slip', ['pres_dept'], 'routing_slip_pres_dept_idx');

        $this->addIndex('doctrack_slip', ['is_archived', 'created_at'], 'doctrack_slip_archived_created_idx');
        $this->addIndex('doctrack_slip', ['is_archived', 'docslip_id'], 'doctrack_slip_archived_docslip_idx');
        $this->addIndex('doctrack_slip', ['docslip_id'], 'doctrack_slip_docslip_id_idx');
        $this->addIndex('doctrack_slip', ['created_at'], 'doctrack_slip_created_at_idx');
        $this->addIndex('doctrack_slip', ['ctrl_no'], 'doctrack_slip_ctrl_no_idx');
    }

    public function down()
    {
        $this->dropIndex('routing_slip', 'routing_slip_archived_created_idx');
        $this->dropIndex('routing_slip', 'routing_slip_archived_rslip_idx');
        $this->dropIndex('routing_slip', 'routing_slip_pres_dept_idx');

        $this->dropIndex('doctrack_slip', 'doctrack_slip_archived_created_idx');
        $this->dropIndex('doctrack_slip', 'doctrack_slip_archived_docslip_idx');
        $this->dropIndex('doctrack_slip', 'doctrack_slip_docslip_id_idx');
        $this->dropIndex('doctrack_slip', 'doctrack_slip_created_at_idx');
        $this->dropIndex('doctrack_slip', 'doctrack_slip_ctrl_no_idx');

        foreach (['routing_slip', 'doctrack_slip'] as $tableName) {
            if (!Schema::hasTable($tableName)) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table) {
                if (Schema::hasColumn($table->getTable(), 'is_archived')) {
                    $table->dropColumn('is_archived');
                }
                if (Schema::hasColumn($table->getTable(), 'archived_at')) {
                    $table->dropColumn('archived_at');
                }
                if (Schema::hasColumn($table->getTable(), 'archived_by')) {
                    $table->dropColumn('archived_by');
                }
            });
        }

        Schema::dropIfExists('archive_logs');
    }

    private function addArchiveColumns(string $tableName): void
    {
        if (!Schema::hasTable($tableName)) {
            return;
        }

        Schema::table($tableName, function (Blueprint $table) use ($tableName) {
            if (!Schema::hasColumn($tableName, 'is_archived')) {
                $table->boolean('is_archived')->default(false);
            }
            if (!Schema::hasColumn($tableName, 'archived_at')) {
                $table->timestamp('archived_at')->nullable();
            }
            if (!Schema::hasColumn($tableName, 'archived_by')) {
                $table->unsignedBigInteger('archived_by')->nullable();
            }
        });
    }

    private function addIndex(string $tableName, array $columns, string $indexName): void
    {
        if (!Schema::hasTable($tableName) || $this->indexExists($tableName, $indexName)) {
            return;
        }

        Schema::table($tableName, function (Blueprint $table) use ($columns, $indexName) {
            $table->index($columns, $indexName);
        });
    }

    private function dropIndex(string $tableName, string $indexName): void
    {
        if (!Schema::hasTable($tableName) || !$this->indexExists($tableName, $indexName)) {
            return;
        }

        Schema::table($tableName, function (Blueprint $table) use ($indexName) {
            $table->dropIndex($indexName);
        });
    }

    private function indexExists(string $tableName, string $indexName): bool
    {
        if (DB::getDriverName() !== 'mysql') {
            return false;
        }

        $result = DB::select(
            'SHOW INDEX FROM `' . str_replace('`', '``', $tableName) . '` WHERE Key_name = ?',
            [$indexName]
        );

        return !empty($result);
    }
};
