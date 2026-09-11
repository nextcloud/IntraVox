<?php

declare(strict_types=1);

namespace OCA\IntraVox\Tests\Unit\Controller\Harness;

use OCA\IntraVox\Service\Cache\PageCacheService;
use OCA\IntraVox\Service\Listing\PageLister;
use OCA\IntraVox\Service\Locator\PageLocator;
use OCA\IntraVox\Service\PageIndexService;
use OCA\IntraVox\Service\Path\PageDataEnricher;
use OCA\IntraVox\Service\PermissionService;
use OCA\IntraVox\Service\Sanitize\PageShapeSanitizer;
use OCA\IntraVox\Tests\Unit\Service\Harness\BuildsPageService;
use OCP\Files\Folder;
use Psr\Log\LoggerInterface;

/**
 * Builds a REAL (final) PageLister whose listAll() returns a controlled page list
 * — the fase-5 Phase II replacement for `createMock(PageService::class)->method('listPages')`
 * once ApiController injects the PageLister directly.
 *
 * PageLister is final (cannot be doubled), and the codebase convention (see
 * BuildsPageRead / PageWalkerSkipTest::lister) is to construct the real domain
 * service over rigged collaborators rather than double the service itself.
 *
 * listAll() takes the index fast-path when the read-language folder resolves to a
 * language whose index hasEntries(): it then returns inStableOrder(fromIndex()),
 * skipping the filesystem walk entirely. So this rigs exactly that path via the
 * injected PageIndexService + PageLocator + PermissionService, and reuses
 * BuildsPageService::fakeFolderContext / makeFolder for the folder substrate.
 * Because inStableOrder sorts by (title, uniqueId), callers that assert on
 * positional output must pass rows whose titles are already in sorted order.
 *
 * The host must be a PHPUnit\Framework\TestCase (uses createMock()).
 */
trait BuildsPageLister {

    use BuildsPageService;

    /**
     * A real PageLister::listAll() returning one entry per given row (index
     * fast-path). Each row: uniqueId, title, and permissions (canRead/canWrite).
     *
     * @param array<int,array{uniqueId:string,title:string,permissions:array<string,bool>}> $rows
     */
    protected function fakePageListerReturning(array $rows): PageLister {
        // The read-language folder throws on get('home.json') so fromIndex's
        // home-guarantee is skipped (no loose homepage), serving the rows as-is.
        // intraVox() is the /IntraVox root one level up so FolderContext's own
        // PageLocator::languageOfFolder derives 'en' from the read folder's first
        // path segment (root===read-folder would resolve to null and drop to the
        // walk).
        $intraVox = $this->makeFolder('/IntraVox');
        $langFolder = $this->makeFolder('/IntraVox/en');
        $folders = $this->fakeFolderContext(readLanguageFolder: $langFolder, intraVox: $intraVox);

        $index = $this->createMock(PageIndexService::class);
        $index->method('hasEntries')->willReturn(true);
        // DB rows in the shape PageLister::fromIndex reads (unique_id / path / title
        // / modified_at / status). A synthetic absolute path per uniqueId lets
        // folderFromAbsolutePath return a distinct folder whose permissionsFromNode
        // is that row's perms.
        $dbRows = [];
        $folderByPath = [];
        $permsByPath = [];
        foreach ($rows as $row) {
            $path = '/IntraVox/en/' . $row['uniqueId'];
            $dbRows[] = [
                'unique_id' => $row['uniqueId'],
                'path' => $path,
                'title' => $row['title'],
                'modified_at' => 0,
                'status' => 'published',
            ];
            $folderByPath[$path] = $this->makeFolder($path);
            $permsByPath[$path] = $row['permissions'];
        }
        $index->method('getPagesByLanguage')->willReturn($dbRows);

        $locator = $this->createMock(PageLocator::class);
        $locator->method('languageOfFolder')->willReturn('en');
        $locator->method('folderFromAbsolutePath')->willReturnCallback(
            fn($root, string $abs): ?Folder => $folderByPath[$abs] ?? null
        );

        $perm = $this->createMock(PermissionService::class);
        $perm->method('permissionsFromNode')->willReturnCallback(
            fn($node): array => $permsByPath[$node->getPath()] ?? ['canRead' => false, 'canWrite' => false]
        );

        return new PageLister(
            $locator,
            $index,
            $perm,
            $this->createMock(LoggerInterface::class),
            $folders,
            $this->doubleOrBuild(PageShapeSanitizer::class),
            $this->createMock(PageCacheService::class),
            $this->doubleOrBuild(PageDataEnricher::class),
        );
    }

    /**
     * A real PageLister whose listAll() throws — for the folder-not-found /
     * database-error controller branches. The read-language folder access raises,
     * propagating verbatim out of listAll() (fromIndex is never reached).
     */
    protected function fakePageListerThrowing(\Throwable $e): PageLister {
        // A read-language seam that throws: FolderContext's readLanguageFolder()
        // returns the given closure's result, so the exception surfaces on listAll's
        // first line ($this->folders->readLanguageFolder()).
        $folders = $this->fakeFolderContextThrowingRead($e);

        return new PageLister(
            $this->createMock(PageLocator::class),
            $this->createMock(PageIndexService::class),
            $this->createMock(PermissionService::class),
            $this->createMock(LoggerInterface::class),
            $folders,
            $this->doubleOrBuild(PageShapeSanitizer::class),
            $this->createMock(PageCacheService::class),
            $this->doubleOrBuild(PageDataEnricher::class),
        );
    }
}
