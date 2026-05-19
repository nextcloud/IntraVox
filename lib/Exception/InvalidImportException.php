<?php
declare(strict_types=1);

namespace OCA\IntraVox\Exception;

/**
 * Thrown by ImportService when the uploaded ZIP fails validation in a way
 * the user can act on (missing export.json, wrong format, unsupported
 * version, incomplete export).
 *
 * Carries both a stable machine-readable error-code and a human-readable
 * English fallback message:
 *
 * - The `errorCode` is what the frontend maps to a translated string
 *   (`AdminSettings.vue::importErrorMessageFor()`). Stable across releases
 *   so translations don't need to track wording tweaks.
 * - The exception `message` is the English fallback returned alongside
 *   the code; rendered by clients that don't speak our code vocabulary
 *   (curl, third-party importers, log files).
 *
 * See GitHub issue #52: users got `Import failed. Please check the ZIP
 * file format` with no hint that they were uploading a Nextcloud-Files
 * backup instead of an IntraVox export.
 */
final class InvalidImportException extends \Exception {
    public const CODE_INVALID_ZIP = 'INVALID_ZIP';
    public const CODE_MISSING_EXPORT_JSON = 'MISSING_EXPORT_JSON';
    public const CODE_INVALID_JSON = 'INVALID_JSON';
    public const CODE_UNSUPPORTED_VERSION = 'UNSUPPORTED_VERSION';
    public const CODE_INCOMPLETE_EXPORT = 'INCOMPLETE_EXPORT';

    private string $errorCode;
    private array $params;

    /**
     * @param string $errorCode One of the CODE_* constants
     * @param string $message English fallback message (used for non-UI consumers)
     * @param array $params Optional context (e.g. version, count) the frontend may interpolate
     */
    public function __construct(string $errorCode, string $message, array $params = []) {
        parent::__construct($message);
        $this->errorCode = $errorCode;
        $this->params = $params;
    }

    public function getErrorCode(): string {
        return $this->errorCode;
    }

    /**
     * @return array<string, mixed>
     */
    public function getParams(): array {
        return $this->params;
    }
}
