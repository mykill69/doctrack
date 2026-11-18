<?php

namespace App\Services;

use App\Models\Esig;
use App\Models\User;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Stores e-signatures ENCRYPTED in storage/app/esignature.
 *
 * - The image is encrypted with the application key (APP_KEY, AES-256 + MAC)
 *   so the file on disk is not a readable image.
 * - The file name is random, it does not contain the user's name.
 * - Signatures are only decrypted in memory (PDF generation / own preview).
 *
 * IMPORTANT: keep a backup of APP_KEY. If APP_KEY changes, the encrypted
 * signatures can no longer be read and every user has to upload again.
 */
class EsignatureService
{
    public const ALLOWED_MIMES = ['image/png', 'image/jpeg'];

    public function directory(): string
    {
        return storage_path('app/esignature');
    }

    /**
     * Absolute path of a file inside storage/app/esignature, or null when the
     * name would point outside of that folder.
     */
    public function pathFor(?string $fileName): ?string
    {
        if (!$fileName || str_contains($fileName, '..') || str_contains($fileName, "\0")) {
            return null;
        }

        return $this->directory() . DIRECTORY_SEPARATOR . $fileName;
    }

    /**
     * Encrypt the uploaded image, save it and replace the user's previous
     * signature (legacy plain file or older encrypted file).
     */
    public function store(User $user, UploadedFile $file): Esig
    {
        $contents = file_get_contents($file->getRealPath());

        // ✅ Check the REAL content of the file, not only the extension
        $info = $contents !== false ? @getimagesizefromstring($contents) : false;
        if (!$info || !in_array($info['mime'], self::ALLOWED_MIMES, true)) {
            throw new \InvalidArgumentException('The e-signature must be a valid PNG or JPG image.');
        }

        if (!is_dir($this->directory())) {
            mkdir($this->directory(), 0755, true);
        }

        $fileName = 'esig_' . $user->id . '_' . Str::random(40) . '.enc';
        file_put_contents($this->pathFor($fileName), Crypt::encryptString($contents), LOCK_EX);

        $existing = Esig::where('user_id', $user->id)->first();
        $oldFile  = $existing?->esig_file;

        $esig = Esig::updateOrCreate(
            ['user_id' => $user->id],
            [
                'esig_file'    => $fileName,
                'is_encrypted' => true,
                'mime_type'    => $info['mime'],
            ]
        );

        // ✅ Remove the previous file (including the old unencrypted one)
        if ($oldFile && $oldFile !== $fileName) {
            $this->deleteFile($oldFile);
        }

        return $esig;
    }

    /**
     * Decrypted image bytes, or null when there is no usable (encrypted) signature.
     * Legacy unencrypted signatures are intentionally NOT returned.
     */
    public function contents(?Esig $esig): ?string
    {
        if (!$esig || !$esig->is_encrypted || !$esig->esig_file) {
            return null;
        }

        $path = $this->pathFor($esig->esig_file);
        if (!$path || !is_file($path)) {
            return null;
        }

        try {
            return Crypt::decryptString(file_get_contents($path));
        } catch (DecryptException $e) {
            Log::warning("Unable to decrypt e-signature of user {$esig->user_id}: " . $e->getMessage());
            return null;
        }
    }

    /**
     * data:image/...;base64,... for <img> tags in the PDFs (dompdf).
     */
    public function dataUri(?Esig $esig): ?string
    {
        $contents = $this->contents($esig);
        if ($contents === null) {
            return null;
        }

        $mime = $esig->mime_type ?: 'image/png';

        return 'data:' . $mime . ';base64,' . base64_encode($contents);
    }

    public function dataUriForUser(?int $userId): ?string
    {
        return $userId ? $this->dataUri(Esig::where('user_id', $userId)->first()) : null;
    }

    public function deleteFile(string $fileName): void
    {
        $path = $this->pathFor($fileName);
        if ($path && is_file($path)) {
            @unlink($path);
        }
    }
}
