<?php
declare(strict_types=1);

namespace OCA\IntraVox\Controller;

use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataResponse;

/**
 * Adds ETag/If-None-Match handling to controllers. Designed to be mixed into
 * subclasses of OCP\AppFramework\Controller that already expose $this->request.
 *
 * Usage:
 *
 *   $etag = EtagBuilder::build($id, $mtime, $userContext);
 *   if ($notModified = $this->respondNotModifiedIfMatches($etag)) {
 *       return $notModified;
 *   }
 *   $response = new DataResponse($payload);
 *   return $this->withCacheHeaders($response, $etag);
 *
 * The trait keeps the cache-control policy conservative: `private,
 * must-revalidate, max-age=0`. That preserves per-user permission boundaries
 * (intermediate proxies won't reuse a response across users) while still
 * letting the browser revalidate via If-None-Match on every navigation,
 * which is where the 304 win comes from.
 */
trait HasConditionalResponse {
    /**
     * Return a 304 DataResponse if the request's If-None-Match matches the
     * supplied ETag, otherwise null. Caller can early-return when non-null.
     */
    protected function respondNotModifiedIfMatches(string $etag): ?DataResponse {
        $ifNoneMatch = $this->request->getHeader('If-None-Match');
        if ($ifNoneMatch !== '' && $ifNoneMatch === $etag) {
            $response = new DataResponse([], Http::STATUS_NOT_MODIFIED);
            $response->addHeader('ETag', $etag);
            $response->addHeader('Cache-Control', 'private, must-revalidate, max-age=0');
            return $response;
        }
        return null;
    }

    /**
     * Attach ETag + Cache-Control headers to a response that is about to be
     * returned with a 200. Returns the same response for chaining.
     */
    protected function withCacheHeaders(DataResponse $response, string $etag, int $maxAge = 0): DataResponse {
        $response->addHeader('ETag', $etag);
        $directive = $maxAge > 0
            ? 'private, max-age=' . $maxAge . ', must-revalidate'
            : 'private, must-revalidate, max-age=0';
        $response->addHeader('Cache-Control', $directive);
        return $response;
    }
}
