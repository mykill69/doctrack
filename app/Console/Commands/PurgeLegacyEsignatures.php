<?php

namespace App\Console\Commands;

use App\Models\Esig;
use App\Services\EsignatureService;
use Illuminate\Console\Command;

/**
 * Deletes the OLD unencrypted e-signature images from storage/app/esignature.
 *
 * The esig rows are kept (is_encrypted = 0), so the users are still reminded
 * to upload their signature again. Encrypted signatures are never touched.
 *
 *   php artisan esig:purge-legacy --dry-run   (only list the files)
 *   php artisan esig:purge-legacy             (delete them)
 */
class PurgeLegacyEsignatures extends Command
{
    protected $signature = 'esig:purge-legacy {--dry-run : Only list the files that would be deleted}';

    protected $description = 'Delete the legacy unencrypted e-signature files (users must re-upload)';

    public function handle(EsignatureService $service): int
    {
        $legacy = Esig::where('is_encrypted', false)->get();

        if ($legacy->isEmpty()) {
            $this->info('No legacy (unencrypted) e-signatures found.');
            return self::SUCCESS;
        }

        $dryRun = (bool) $this->option('dry-run');

        if (!$dryRun && !$this->confirm("Delete {$legacy->count()} unencrypted e-signature file(s)? Users will have to re-upload.")) {
            $this->warn('Cancelled.');
            return self::SUCCESS;
        }

        $deleted = 0;
        foreach ($legacy as $esig) {
            $path = $service->pathFor($esig->esig_file);
            $exists = $path && is_file($path);

            $this->line(sprintf(
                '%s user_id=%d  %s',
                $exists ? ($dryRun ? '[would delete]' : '[deleted]') : '[missing]',
                $esig->user_id,
                $esig->esig_file
            ));

            if ($exists && !$dryRun) {
                $service->deleteFile($esig->esig_file);
                $deleted++;
            }
        }

        $this->info($dryRun ? 'Dry run only, nothing deleted.' : "{$deleted} file(s) deleted.");

        return self::SUCCESS;
    }
}
