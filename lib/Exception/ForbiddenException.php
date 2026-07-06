<?php
declare(strict_types=1);

namespace OCA\IntraVox\Exception;

/**
 * Thrown when a write operation is attempted on a page/folder the current user
 * is not allowed to modify — e.g. a read-only GroupFolder / Team Folder member.
 *
 * Controllers map this to HTTP 403 Forbidden. It extends \RuntimeException (not
 * \InvalidArgumentException) on purpose, so the existing
 * InvalidArgumentException -> 400 catch arms cannot swallow it: a permission
 * problem must read as "forbidden", never "bad request" (issue #70).
 */
final class ForbiddenException extends \RuntimeException {
}
