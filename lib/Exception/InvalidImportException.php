<?php
declare(strict_types=1);

namespace OCA\IntraVox\Exception;

/**
 * Thrown by ImportService when the uploaded ZIP fails validation in a way
 * the user can act on (missing export.json, wrong format, unsupported
 * version, incomplete export).
 *
 * The message is intentionally safe to forward to the API client — no
 * filesystem paths, no internal IDs. Controllers may pass `getMessage()`
 * directly to the response. Generic `\Exception` from ImportService is
 * still hidden behind a "check the ZIP and try again" placeholder,
 * because those tend to carry path/permission detail.
 *
 * See GitHub issue #52: users got `Import failed. Please check the ZIP
 * file format` with no hint that they were uploading a Nextcloud-Files
 * backup instead of an IntraVox export.
 */
final class InvalidImportException extends \Exception {
}
