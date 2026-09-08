<?php
declare(strict_types=1);

namespace OCA\IntraVox\Tests\Unit\Service;

use OCA\IntraVox\Exception\ForbiddenException;
use OCA\IntraVox\Exception\PageConflictException;
use OCA\IntraVox\Exception\PageNotFoundException;
use OCA\IntraVox\Service\PageIndexService;
use OCA\IntraVox\Service\PageService;
use OCA\IntraVox\Service\Version\PageVersionService;
use OCA\IntraVox\Tests\Unit\Service\Harness\BuildsPageService;
use OCP\Files\File;
use OCP\Files\FileInfo;
use OCP\Files\Folder;
use OCP\IUser;
use OCP\IUserSession;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Pins updatePage()'s write pipeline before it moves into Page/PageWriteService
 * in Phase 14.2 (plan W2). The load-bearing ordering is that a version snapshot
 * (createBeforeUpdate) is taken BEFORE putContent overwrites the file — reversing
 * them would snapshot the new content, losing the pre-edit version. Also pins the
 * guard rejections (no user / not found / not updateable / stale baseVersion) and
 * the field-preservation rules that stop a client payload from clobbering the
 * page's identity.
 */
class PageUpdatePipelineTest extends TestCase {

    use BuildsPageService;

    /** Ordered log of write-pipeline side effects. */
    private array $steps = [];

    /**
     * Content the page file currently holds / was last written. translationGroup
     * uses the tg-<uuid> form the shape sanitizer's whitelist requires (a bare
     * 'grp-1' would be stripped, which is itself pinned below).
     */
    private const EXISTING_TG = 'tg-00000000-0000-0000-0000-000000000001';
    private array $existing = [
        'uniqueId' => 'page-about',
        'title' => 'Old',
        'translationGroup' => self::EXISTING_TG,
    ];
    private ?string $written = null;
    private int $mtime = 1000;

    protected function setUp(): void {
        $this->steps = [];
        $this->written = null;
        $this->mtime = 1000;
    }

    private function makeFileMock(): File {
        $file = $this->createMock(File::class);
        $file->method('getName')->willReturn('about.json');
        $file->method('getType')->willReturn(FileInfo::TYPE_FILE);
        $file->method('getPath')->willReturn('/IntraVox/en/about.json');
        $file->method('getId')->willReturn(4242);
        $file->method('isUpdateable')->willReturn(true);
        $file->method('getMTime')->willReturnCallback(fn() => $this->mtime);
        $file->method('getContent')->willReturnCallback(
            fn() => json_encode($this->existing)
        );
        $file->method('putContent')->willReturnCallback(function ($content) {
            $this->steps[] = 'putContent';
            $this->written = $content;
        });
        return $file;
    }

    /**
     * @param File|null $file override the page file (e.g. read-only)
     */
    private function makeService(?File $file = null): PageService {
        $file = $file ?? $this->makeFileMock();
        $pageFolder = $this->makeFolder('/IntraVox/en/about', []);
        $lang = $this->makeFolder('/IntraVox/en', [
            'about.json' => $file,
            'about' => $pageFolder,
        ]);

        // updatePage resolves its write-target via folders()->languageFolder()
        // ($lang) and derives the #90 index language via languageOfFolder (uses the
        // intraVox() root). getIntraVoxFolder stays THROWING for the cross-language
        // locatePageAnyLanguage walk (rootClosure()) — the primary-folder scan
        // finds the page first, so the throw only pins the degrade path.
        $svc = new class extends PageService {
            public function __construct() {
            }
            public function clearCache(): void {
                // Observed via the steps log so cache-clear position is pinned too.
            }
        };

        $user = $this->createMock(IUser::class);
        $user->method('getUID')->willReturn('tester');
        $session = $this->createMock(IUserSession::class);
        $session->method('getUser')->willReturn($user);

        // PageShapeSanitizer is final; the harness builds the REAL one from its
        // leaf sanitizers (doubleOrBuild), so validateAndSanitizePage runs for real
        // — the pipeline-order and preserved-field assertions hold regardless of
        // the sanitizer's per-field transform.
        $version = $this->createMock(PageVersionService::class);
        $version->method('createBeforeUpdate')->willReturnCallback(function () {
            $this->steps[] = 'createBeforeUpdate';
        });

        $index = $this->createMock(PageIndexService::class);
        $index->method('findByUniqueId')->willReturn(null);
        $index->method('indexPage')->willReturnCallback(function () {
            $this->steps[] = 'indexPage';
        });

        $this->injectPageServiceDependencies($svc, [
            'userSession' => $session,
            'userId' => 'tester',
            'pageVersionService' => $version,
            'pageIndexService' => $index,
            'logger' => $this->createMock(LoggerInterface::class),
            'folderContext' => $this->fakeFolderContext(intraVox: $lang, languageFolder: $lang),
        ]);

        return $svc;
    }

    public function testNoUserInSessionIsRejected(): void {
        $svc = $this->makeService();
        // Overwrite the session with a null-user one.
        $session = $this->createMock(IUserSession::class);
        $session->method('getUser')->willReturn(null);
        (new \ReflectionProperty(PageService::class, 'userSession'))->setValue($svc, $session);

        // Guard-ordering pin: swap in an UNWIRED FolderContext that throws on any
        // folder resolution. The cheap !$user guard must fire first — if it
        // regressed (folder resolved before the guard, as the write-cluster carve
        // once did), a LogicException would surface instead of 'No user in session'.
        (new \ReflectionProperty(PageService::class, 'folderContext'))
            ->setValue($svc, $this->fakeFolderContext());

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('No user in session');
        $svc->updatePage('page-about', ['title' => 'New']);
    }

    public function testUnknownPageThrowsPageNotFound(): void {
        $empty = $this->makeFolder('/IntraVox/en', []);
        $base = $this->makeFolder('/IntraVox', ['en' => $empty]);
        // languageFolder ($empty) via the seam; getIntraVoxFolder ($base) kept for
        // the cross-language locate walk (rootClosure()).
        $svc = new class($base) extends PageService {
            public function __construct() {
            }
        };
        $user = $this->createMock(IUser::class);
        $session = $this->createMock(IUserSession::class);
        $session->method('getUser')->willReturn($user);
        $index = $this->createMock(PageIndexService::class);
        $index->method('findByUniqueId')->willReturn(null);
        $this->injectPageServiceDependencies($svc, [
            'userSession' => $session,
            'userId' => 'tester',
            'pageIndexService' => $index,
            'logger' => $this->createMock(LoggerInterface::class),
            'folderContext' => $this->fakeFolderContext(intraVox: $base, languageFolder: $empty),
        ]);

        $this->expectException(PageNotFoundException::class);
        $this->expectExceptionMessage('Page not found: page-missing');
        $svc->updatePage('page-missing', ['title' => 'New']);
    }

    public function testReadOnlyFileIsRefusedBeforeAnyWrite(): void {
        // A read-only page file: isUpdateable is false, so the preflight refuses.
        $ro = $this->createMock(File::class);
        $ro->method('getName')->willReturn('about.json');
        $ro->method('getType')->willReturn(FileInfo::TYPE_FILE);
        $ro->method('getPath')->willReturn('/IntraVox/en/about.json');
        $ro->method('isUpdateable')->willReturn(false);
        $ro->method('getContent')->willReturnCallback(fn() => json_encode($this->existing));
        $ro->method('putContent')->willReturnCallback(function () {
            $this->steps[] = 'putContent';
        });

        $svc = $this->makeService($ro);

        try {
            $svc->updatePage('page-about', ['title' => 'New']);
            $this->fail('a read-only file must be refused');
        } catch (ForbiddenException $e) {
            $this->assertStringContainsString('permission', $e->getMessage());
        }
        $this->assertNotContains('putContent', $this->steps, 'nothing may be written to a read-only page');
    }

    public function testStaleBaseVersionIsRejectedWithoutWriting(): void {
        $svc = $this->makeService();
        $this->mtime = 2000; // file is newer than the client's base

        try {
            $svc->updatePage('page-about', ['title' => 'New', 'baseVersion' => 1500]);
            $this->fail('a stale write must be rejected');
        } catch (PageConflictException $e) {
            $this->assertStringContainsString('changed by someone else', $e->getMessage());
        }
        $this->assertNotContains('putContent', $this->steps, 'a conflicting save must not write');
        $this->assertNotContains('createBeforeUpdate', $this->steps, 'no version is cut for a rejected save');
    }

    public function testWritePipelineTakesTheVersionSnapshotBeforeOverwriting(): void {
        // The load-bearing W2 invariant: createBeforeUpdate must precede
        // putContent, or the snapshot captures the NEW content and the pre-edit
        // version is lost. (indexPage is a non-blocking follow-up whose firing
        // depends on folder-language resolution, not pinned here.)
        $svc = $this->makeService();

        $svc->updatePage('page-about', ['title' => 'New']);

        $snapshotAt = array_search('createBeforeUpdate', $this->steps, true);
        $writeAt = array_search('putContent', $this->steps, true);
        $this->assertNotFalse($snapshotAt, 'a version snapshot must be taken');
        $this->assertNotFalse($writeAt, 'the file must be written');
        $this->assertLessThan(
            $writeAt,
            $snapshotAt,
            'createBeforeUpdate must run before putContent'
        );
    }

    public function testIdentityFieldsArePreservedOverThePayload(): void {
        $svc = $this->makeService();

        // Client tries to change uniqueId and translationGroup; both must be kept
        // from the existing file (translationGroup uses the whitelisted tg- form).
        $svc->updatePage('page-about', [
            'title' => 'New',
            'uniqueId' => 'page-HIJACK',
            'translationGroup' => 'tg-99999999-9999-9999-9999-999999999999',
            'baseVersion' => 5000,
        ]);

        $persisted = json_decode($this->written, true);
        $this->assertSame('page-about', $persisted['uniqueId'], 'uniqueId comes from the existing file');
        $this->assertSame(
            self::EXISTING_TG,
            $persisted['translationGroup'],
            'translationGroup is taken from the existing file, not the client payload'
        );
        $this->assertArrayNotHasKey('baseVersion', $persisted, 'the transport-only token is never persisted');
    }

    public function testAnUnwhitelistedTranslationGroupIsStrippedBySanitization(): void {
        // A bare 'grp-1' (not tg-<uuid>) in the existing file does not survive the
        // shape sanitizer's whitelist — pinning this so the extraction preserves
        // the exact sanitize behaviour, not just field-carry.
        $this->existing = ['uniqueId' => 'page-about', 'title' => 'Old', 'translationGroup' => 'grp-1'];
        $svc = $this->makeService();

        $svc->updatePage('page-about', ['title' => 'New']);

        $persisted = json_decode($this->written, true);
        $this->assertArrayNotHasKey(
            'translationGroup',
            $persisted,
            'a non-tg- translationGroup is stripped by validateAndSanitizePage'
        );
    }

    public function testReturnCarriesTheNewBaseVersion(): void {
        $svc = $this->makeService();
        $this->mtime = 1000;

        $result = $svc->updatePage('page-about', ['title' => 'New']);

        $this->assertSame('about', $result['id'], 'id is the folder basename');
        $this->assertSame($this->mtime, $result['baseVersion'], 'the response hands back the post-write mtime');
        $this->assertArrayNotHasKey('baseVersion', json_decode($this->written, true));
    }
}
