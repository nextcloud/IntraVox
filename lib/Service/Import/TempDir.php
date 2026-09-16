<?php
declare(strict_types=1);

namespace OCA\IntraVox\Service\Import;

/**
 * Recursive cleanup of a temporary working directory.
 *
 * The import/export flows extract archives into a scratch dir and must remove it
 * afterwards. The same CHILD_FIRST unlink/rmdir walk was copied verbatim into
 * ImportService, ConfluenceHtmlImportOrchestrator and ExportController; it lives
 * here once. Static because it is a pure filesystem operation with no state and
 * no collaborators — no DI wiring, so the three callers reach it directly.
 */
final class TempDir {

    public static function cleanup(string $dir): void {
        if (!is_dir($dir)) {
            return;
        }

        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($files as $file) {
            if ($file->isDir()) {
                @rmdir($file->getPathname());
            } else {
                @unlink($file->getPathname());
            }
        }
        @rmdir($dir);
    }
}
