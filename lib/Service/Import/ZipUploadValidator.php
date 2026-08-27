<?php
declare(strict_types=1);

namespace OCA\IntraVox\Service\Import;

use OCA\IntraVox\Exception\InvalidImportException;

/**
 * Is this upload actually a ZIP? (controller split, PR-B)
 *
 * Both import endpoints in ApiController opened the uploaded file by hand —
 * fopen/fread/fclose plus a finfo probe — to check the PK magic bytes. Raw file
 * handling in an HTTP controller, duplicated, and with a latent bug: fopen()
 * returns false on an unreadable temp file and fread(false, 4) is a TypeError,
 * which the surrounding catch (\Exception) would not have stopped.
 *
 * Why both checks and not just one: finfo reads the file's own content but is
 * permissive about ZIP-like containers (it reports application/octet-stream for
 * plenty of things), while the PK check is exact but trivial to forge. Neither
 * is a security boundary on its own — SafeZipExtractor is that — so this is a
 * fail-fast on obvious mistakes, not a gate. Kept as it was.
 *
 * Reuses CODE_INVALID_ZIP rather than inventing finer-grained codes: the
 * frontend already translates that one (AdminSettings::importErrorMessageFor),
 * and a new code would surface untranslated to the user.
 */
class ZipUploadValidator {
    private const ACCEPTED_MIMES = [
        'application/zip',
        'application/x-zip-compressed',
        'application/octet-stream',
    ];

    /**
     * @param array<string, mixed> $file the $_FILES-shaped entry from IRequest::getUploadedFile()
     * @throws InvalidImportException when the upload is not a usable ZIP
     */
    public function assertIsZip(array $file): void {
        $path = $file['tmp_name'] ?? '';
        if (!is_string($path) || $path === '' || !is_readable($path)) {
            throw new InvalidImportException(
                InvalidImportException::CODE_INVALID_ZIP,
                'Invalid ZIP file format'
            );
        }

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($path);

        if (!in_array($mimeType, self::ACCEPTED_MIMES, true)) {
            throw new InvalidImportException(
                InvalidImportException::CODE_INVALID_ZIP,
                'Invalid file type. Expected ZIP file, got: ' . $mimeType
            );
        }

        // ZIP files start with PK (0x50 0x4B).
        $handle = fopen($path, 'rb');
        if ($handle === false) {
            throw new InvalidImportException(
                InvalidImportException::CODE_INVALID_ZIP,
                'Invalid ZIP file format'
            );
        }

        try {
            $header = fread($handle, 4);
        } finally {
            fclose($handle);
        }

        if (!is_string($header) || substr($header, 0, 2) !== 'PK') {
            throw new InvalidImportException(
                InvalidImportException::CODE_INVALID_ZIP,
                'Invalid ZIP file format'
            );
        }
    }
}
