<?php
declare(strict_types=1);

namespace OCA\IntraVox\Controller;

use OCA\IntraVox\Service\PageService;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataResponse;

/**
 * One way to ask "may this user write to this page?" (controller split, PR-B).
 *
 * Eight endpoints in ApiController each carried their own copy:
 *
 *     $page = $this->pageService->getPage($id);
 *     if (!($page['permissions']['canWrite'] ?? false)) { ... 403 ... }
 *
 * Identical apart from the message. Eight copies of a security check is eight
 * chances to forget the ninth, and the `?? false` is the part that matters:
 * a page whose permissions could not be determined must read as "no", never as
 * "unset, so allow".
 *
 * Returns the page on success and a DataResponse on refusal, so a caller cannot
 * accidentally continue past a denial — it has to handle the Response to get at
 * the page. That is the whole point of returning a union rather than a bool.
 *
 * Usage:
 *     $page = $this->requireWritablePage($id, 'cannot edit this page');
 *     if ($page instanceof DataResponse) {
 *         return $page;
 *     }
 */
trait RequiresPagePermission {
    /**
     * @return array<string, mixed>|DataResponse the page, or the refusal to return
     */
    protected function requireWritablePage(string $uniqueId, string $denialReason) {
        $page = $this->getPageService()->getPage($uniqueId);

        if (!($page['permissions']['canWrite'] ?? false)) {
            // Two callers historically answered with the bare phrase; keeping
            // that means consolidating does not change a single response body.
            $message = $denialReason === ''
                ? 'Permission denied'
                : 'Permission denied: ' . $denialReason;

            return new DataResponse(
                ['error' => $message],
                Http::STATUS_FORBIDDEN
            );
        }

        return $page;
    }

    /**
     * Explicit accessor rather than reaching for $this->pageService directly:
     * the Shared/ traits do the latter and a controller that forgets the
     * property only finds out at runtime, on the first request.
     */
    abstract protected function getPageService(): PageService;
}
