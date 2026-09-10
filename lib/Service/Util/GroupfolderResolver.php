<?php

declare(strict_types=1);

namespace OCA\IntraVox\Service\Util;

use OCP\Files\Node;

/**
 * Resolves the GroupFolder id a filesystem node lives in — the value MetaVox
 * needs to look a page's fields up, exposed on a page read as `groupfolderId`.
 *
 * A stateless pure function lifted verbatim from PageService::groupfolderIdForNode
 * so PageReadService and PageDataEnricher (which both needed it) can inject it
 * directly instead of receiving it as a $this-bound closure — part of making
 * those two services DI-first-class (facade elimination phase 2). It carries no
 * user/session/#70 state: it reads only the node's own mount/storage.
 */
final class GroupfolderResolver {

    public function forNode(Node $node): ?int {
        try {
            // The mount knows its own folder id. Note that getPath() is NOT a
            // source for this: it returns the per-user mount path
            // (/Rik/files/IntraVox/…), not /__groupfolders/{id}/…, so parsing
            // it yields nothing.
            $mount = $node->getMountPoint();
            if (method_exists($mount, 'getFolderId')) {
                return (int)$mount->getFolderId();
            }

            // Fallback for mount types that do not expose it: the storage id
            // still carries the folder id (local::…/__groupfolders/1/).
            if (preg_match('#/__groupfolders/(\d+)/#', $node->getStorage()->getId(), $m)) {
                return (int)$m[1];
            }
        } catch (\Throwable $e) {
            // A node whose mount cannot be read is not worth failing the page
            // response over; the MetaVox tab simply stays empty.
        }
        return null;
    }
}
