<?php

namespace App\Services;

use App\Models\ArchiveLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ArchiveService
{
    public const TYPE_ROUTING_SLIP = 'routing_slip';
    public const TYPE_DOCUMENT_TRACKING = 'document_tracking';
    public const TYPE_BOTH = 'both';

    /**
     * How many transactions are updated per batch. The transaction ids are
     * read with a keyset (rslip_id/docslip_id > last id) so no huge list is
     * ever loaded into memory.
     */
    public const BATCH_SIZE = 500;

    /**
     * Previous transaction years, based on the ORIGINAL creation date of the
     * transaction (MIN(created_at) of all rows of the same transaction).
     *
     * @return array<int, int>
     */
    public function availableYears(): array
    {
        $currentYear = (int) now()->year;

        $routingYears = DB::query()
            ->fromSub(
                DB::table('routing_slip')
                    ->selectRaw('YEAR(MIN(created_at)) as archive_year')
                    ->where('is_archived', 0)
                    ->whereNotNull('created_at')
                    ->groupBy('rslip_id'),
                'routing_years'
            )
            ->select('archive_year')
            ->whereNotNull('archive_year')
            ->where('archive_year', '<', $currentYear)
            ->distinct()
            ->pluck('archive_year');

        $documentYears = DB::query()
            ->fromSub(
                DB::table('doctrack_slip')
                    ->selectRaw('YEAR(MIN(created_at)) as archive_year')
                    ->where('is_archived', 0)
                    ->whereNotNull('created_at')
                    ->groupBy('docslip_id'),
                'document_years'
            )
            ->select('archive_year')
            ->whereNotNull('archive_year')
            ->where('archive_year', '<', $currentYear)
            ->distinct()
            ->pluck('archive_year');

        return $routingYears
            ->merge($documentYears)
            ->map(fn ($year) => (int) $year)
            ->unique()
            ->sortDesc()
            ->values()
            ->all();
    }

    /**
     * Count the transactions that would be archived for a given year.
     *
     * @return array{routing_slip:int, document_tracking:int, total:int}
     */
    public function preview(int $year): array
    {
        $routing = $this->countEligible(self::TYPE_ROUTING_SLIP, $year);
        $document = $this->countEligible(self::TYPE_DOCUMENT_TRACKING, $year);

        return [
            'routing_slip' => $routing,
            'document_tracking' => $document,
            'total' => $routing + $document,
        ];
    }

    private function countEligible(string $type, int $year): int
    {
        [$table, $key] = $this->tableAndKey($type);

        $transactions = DB::table($table)
            ->select($key)
            ->where('is_archived', 0)
            ->whereNotNull('created_at')
            ->groupBy($key)
            ->havingRaw('YEAR(MIN(created_at)) = ?', [$year]);

        return (int) DB::query()->fromSub($transactions, 'eligible_transactions')->count();
    }

    /**
     * Archive one year and one type. The service returns the number of
     * transactions that were archived and never deletes or rewrites any of the
     * transaction data (only the archival metadata is changed).
     *
     * @return array<string, array{records:int, status:string, message:?string}>
     */
    public function archiveYear(int $year, string $type, int $userId): array
    {
        $types = $type === self::TYPE_BOTH
            ? [self::TYPE_ROUTING_SLIP, self::TYPE_DOCUMENT_TRACKING]
            : [$type];

        $results = [];

        foreach ($types as $currentType) {
            $logId = DB::table('archive_logs')->insertGetId([
                'archive_year' => $year,
                'transaction_type' => $currentType,
                'records_count' => 0,
                'archived_by' => $userId,
                'archived_at' => now(),
                'status' => ArchiveLog::STATUS_PROCESSING,
                'message' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            try {
                $records = $currentType === self::TYPE_ROUTING_SLIP
                    ? $this->archiveRoutingSlipYear($year, $userId)
                    : $this->archiveDocumentTrackingYear($year, $userId);

                $status = $records > 0
                    ? ArchiveLog::STATUS_COMPLETED
                    : ArchiveLog::STATUS_SKIPPED;

                $message = $records > 0
                    ? null
                    : 'No eligible records remained for this year (they were already archived).';

                DB::table('archive_logs')->where('id', $logId)->update([
                    'records_count' => $records,
                    'status' => $status,
                    'message' => $message,
                    'archived_at' => now(),
                    'updated_at' => now(),
                ]);

                $results[$currentType] = [
                    'records' => $records,
                    'status' => $status,
                    'message' => $message,
                ];
            } catch (\Throwable $exception) {
                Log::error('Archive Logbook operation failed', [
                    'year' => $year,
                    'type' => $currentType,
                    'user_id' => $userId,
                    'error' => $exception->getMessage(),
                ]);

                DB::table('archive_logs')->where('id', $logId)->update([
                    'status' => ArchiveLog::STATUS_FAILED,
                    'message' => Str::limit($exception->getMessage(), 900),
                    'updated_at' => now(),
                ]);

                $results[$currentType] = [
                    'records' => 0,
                    'status' => ArchiveLog::STATUS_FAILED,
                    'message' => $exception->getMessage(),
                ];
            }
        }

        return $results;
    }

    /**
     * Archive every row of each routing slip transaction whose original
     * creation year matches. The primary keys are read in small, ordered
     * batches and each batch is a single UPDATE statement.
     */
    private function archiveRoutingSlipYear(int $year, int $userId): int
    {
        return $this->archiveInBatches(
            'routing_slip',
            'rslip_id',
            $year,
            $userId
        );
    }

    private function archiveDocumentTrackingYear(int $year, int $userId): int
    {
        return $this->archiveInBatches(
            'doctrack_slip',
            'docslip_id',
            $year,
            $userId
        );
    }

    private function archiveInBatches(string $table, string $key, int $year, int $userId): int
    {
        $archivedTransactions = 0;
        $lastKey = null;

        do {
            $query = DB::table($table)
                ->select($key)
                ->where('is_archived', 0)
                ->whereNotNull('created_at')
                ->groupBy($key)
                ->havingRaw('YEAR(MIN(created_at)) = ?', [$year])
                ->orderBy($key);

            if ($lastKey !== null) {
                $query->where($key, '>', $lastKey);
            }

            $transactionKeys = $query->limit(self::BATCH_SIZE)->pluck($key)->all();

            if (empty($transactionKeys)) {
                break;
            }

            DB::table($table)
                ->whereIn($key, $transactionKeys)
                ->where('is_archived', 0)
                ->update([
                    'is_archived' => 1,
                    'archived_at' => now(),
                    'archived_by' => $userId,
                ]);

            $archivedTransactions += count($transactionKeys);
            $lastKey = end($transactionKeys);
        } while (count($transactionKeys) === self::BATCH_SIZE);

        return $archivedTransactions;
    }

    public function isRoutingSlipArchived($rslipId): bool
    {
        return DB::table('routing_slip')
            ->where('rslip_id', $rslipId)
            ->where('is_archived', 1)
            ->exists();
    }

    public function isDocumentTrackingArchived($docslipId): bool
    {
        return DB::table('doctrack_slip')
            ->where('docslip_id', $docslipId)
            ->where('is_archived', 1)
            ->exists();
    }

    /**
     * Used by the normal operational routes to stop archived transactions
     * from being opened or edited with a direct URL.
     */
    public function isRouteArchived($routeId): bool
    {
        return $this->isRoutingSlipArchived($routeId)
            || $this->isDocumentTrackingArchived($routeId);
    }

    /**
     * Archived years that are actually present in the history page filters.
     *
     * @return array<int, int>
     */
    public function archivedYears(): array
    {
        $routingYears = DB::query()
            ->fromSub(
                DB::table('routing_slip')
                    ->selectRaw('YEAR(MIN(created_at)) as archive_year')
                    ->where('is_archived', 1)
                    ->whereNotNull('created_at')
                    ->groupBy('rslip_id'),
                'archived_routing_years'
            )
            ->select('archive_year')
            ->whereNotNull('archive_year')
            ->distinct()
            ->pluck('archive_year');

        $documentYears = DB::query()
            ->fromSub(
                DB::table('doctrack_slip')
                    ->selectRaw('YEAR(MIN(created_at)) as archive_year')
                    ->where('is_archived', 1)
                    ->whereNotNull('created_at')
                    ->groupBy('docslip_id'),
                'archived_document_years'
            )
            ->select('archive_year')
            ->whereNotNull('archive_year')
            ->distinct()
            ->pluck('archive_year');

        return $routingYears
            ->merge($documentYears)
            ->map(fn ($year) => (int) $year)
            ->unique()
            ->sortDesc()
            ->values()
            ->all();
    }

    /**
     * @return array{0:string,1:string}
     */
    private function tableAndKey(string $type): array
    {
        return $type === self::TYPE_ROUTING_SLIP
            ? ['routing_slip', 'rslip_id']
            : ['doctrack_slip', 'docslip_id'];
    }
}
