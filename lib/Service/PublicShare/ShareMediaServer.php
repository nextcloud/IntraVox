<?php
declare(strict_types=1);

namespace OCA\IntraVox\Service\PublicShare;

use OCP\Files\File;
use OCP\Files\FileInfo;
use OCP\Files\Folder;
use OCP\Files\Node;
use OCP\Files\NotFoundException;
use OCP\AppFramework\Http\StreamResponse;

/**
 * Serving a file to an anonymous share visitor, in one place.
 *
 * getMediaByShare and getResourcesMediaByShare each carried their own copy of
 * "walk to the folder, check it is a file, check the MIME, build a
 * StreamResponse with security headers". Two copies of a response that goes to
 * unauthenticated callers is two places for a header to be forgotten.
 *
 * WHAT IS DELIBERATELY NOT SHARED WITH PageMediaService::streamMediaFile():
 *
 *   - The MIME allowlist. The anonymous one is a strict SUPERSET of
 *     ALLOWED_MEDIA_TYPES: it also permits image/bmp and video/quicktime.
 *     Reusing the service constant would silently stop serving .bmp and .mov
 *     files that existing public shares link to. Measured, not assumed.
 *   - The hardening headers. The anonymous responses add X-Content-Type-Options
 *     and X-Frame-Options; the authenticated path does not. Those belong on a
 *     response served to the open internet, so they stay.
 *
 * What IS carried over from that service is the MIME fallback: GroupFolder's
 * cache sometimes reports application/octet-stream for a file it has not
 * probed, and without the extension fallback a perfectly good PNG 404s.
 */
final class ShareMediaServer {
    /**
     * Media an anonymous visitor may fetch.
     *
     * Wider than PageMediaService::ALLOWED_MEDIA_TYPES by image/bmp and
     * video/quicktime — see the class docblock. Kept as its own list precisely
     * so narrowing it is a visible decision rather than a side effect.
     */
    public const ANONYMOUS_MEDIA_TYPES = [
        'image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml', 'image/bmp',
        'video/mp4', 'video/webm', 'video/ogg', 'video/quicktime',
    ];

    private const VIDEO_PREFIX = 'video/';

    /**
     * Resolve a file inside a folder, or null when it is missing or not a file.
     *
     * Returns null rather than throwing, because every caller answers the same
     * 404 for "no such file" as for "not a file": an anonymous visitor learns
     * nothing about which of the two it was.
     */
    public function fileIn(Folder $folder, string $relativePath): ?File {
        try {
            $node = $folder->get($relativePath);
        } catch (NotFoundException $e) {
            return null;
        }

        if (!$node instanceof File || $node->getType() !== FileInfo::TYPE_FILE) {
            return null;
        }

        return $node;
    }

    /**
     * Walk a chain of folder names, returning null if any step is missing or is
     * not a folder.
     *
     * @param string[] $names
     */
    public function folderIn(Folder $root, array $names): ?Folder {
        $current = $root;
        foreach ($names as $name) {
            try {
                $next = $current->get($name);
            } catch (NotFoundException $e) {
                return null;
            }
            if (!$next instanceof Folder) {
                return null;
            }
            $current = $next;
        }
        return $current;
    }

    /**
     * The MIME we will serve this file as, with the GroupFolder-cache fallback.
     */
    public function mimeTypeOf(Node $file): string {
        $mimeType = $file->getMimeType();

        if ($mimeType === 'application/octet-stream') {
            $ext = strtolower(pathinfo($file->getName(), PATHINFO_EXTENSION));
            $mimeType = match ($ext) {
                'jpg', 'jpeg' => 'image/jpeg',
                'png' => 'image/png',
                'gif' => 'image/gif',
                'webp' => 'image/webp',
                'bmp' => 'image/bmp',
                'svg' => 'image/svg+xml',
                'mp4' => 'video/mp4',
                'webm' => 'video/webm',
                'ogg' => 'video/ogg',
                'mov' => 'video/quicktime',
                default => $mimeType,
            };
        }

        return $mimeType;
    }

    public function isServableMedia(string $mimeType): bool {
        return in_array($mimeType, self::ANONYMOUS_MEDIA_TYPES, true);
    }

    /**
     * Build the streaming response, headers included.
     *
     * @param string|null $downloadName overrides the name in Content-Disposition
     *                                  (the resources endpoint uses the requested
     *                                  path's basename, not the file's own name)
     * @param bool $enforceAllowlist    false for _resources, which historically
     *                                  serves whatever MIME the file reports
     */
    public function stream(File $file, ?string $downloadName = null, bool $enforceAllowlist = true): ?StreamResponse {
        $mimeType = $this->mimeTypeOf($file);

        if ($enforceAllowlist && !$this->isServableMedia($mimeType)) {
            return null;
        }

        $handle = $file->fopen('rb');
        if ($handle === false) {
            return null;
        }

        $response = new StreamResponse($handle);
        $response->addHeader('Content-Type', $mimeType);
        $response->addHeader('Content-Disposition', 'inline; filename="' . ($downloadName ?? $file->getName()) . '"');
        // Served to the open internet: never let a browser sniff a different
        // type out of the bytes, and never let the response be framed.
        $response->addHeader('X-Content-Type-Options', 'nosniff');
        $response->addHeader('X-Frame-Options', 'DENY');

        $isVideo = str_starts_with($mimeType, self::VIDEO_PREFIX);
        $response->addHeader('Cache-Control', 'public, max-age=' . ($isVideo ? 86400 : 31536000));

        return $response;
    }
}
