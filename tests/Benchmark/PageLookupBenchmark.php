<?php
declare(strict_types=1);

namespace OCA\IntraVox\Tests\Benchmark;

use OCA\IntraVox\Service\PageService;
use OCP\Files\File;
use OCP\Files\FileInfo;
use OCP\Files\Folder;
use PHPUnit\Framework\TestCase;

/**
 * Baseline measurement for the page-lookup cost, ahead of the 2.0 index work.
 *
 * This is a MEASUREMENT, not a pass/fail test. It exists because "the index
 * made it faster" is only a claim until there is a number from before. It
 * counts the two operations that actually dominate — file reads and
 * json_decode calls — rather than wall-clock, which on a mocked filesystem
 * would measure PHPUnit rather than IntraVox.
 *
 * The counts are what the diagnosis predicts:
 *   - findPageByUniqueId() walks the tree and parses every page JSON: O(N)
 *   - locateAcrossLanguages() repeats that per language folder: O(N x L)
 *   - a MISS pays the full O(N x L) every time — the common case for a stale
 *     link, a deleted page, or an id probe.
 *
 * Run:  vendor/bin/phpunit --testsuite Benchmark
 */
/**
 * Minimal File stand-in. Only the methods findPageByUniqueId() actually calls
 * are implemented; anything else would be dead weight at 18k instances.
 */
final class BenchFile implements File {
    private string $json;

    public function __construct(
        private string $path,
        string $uniqueId,
        private PageLookupBenchmark $bench
    ) {
        $this->json = json_encode([
            'uniqueId' => $uniqueId,
            'title' => basename($path, '.json'),
            'language' => explode('/', trim($path, '/'))[1] ?? 'en',
            // A realistic page carries widgets; size drives the decode cost.
            'layout' => array_fill(0, 8, ['type' => 'text', 'content' => str_repeat('x', 200)]),
        ]);
    }

    public function getName(): string {
        return basename($this->path);
    }
    public function getPath(): string {
        return $this->path;
    }
    public function getType(): int {
        return FileInfo::TYPE_FILE;
    }
    public function getId(): int {
        return abs(crc32($this->path));
    }
    public function getContent(): string {
        $this->bench->countRead();
        return $this->json;
    }
    public function putContent($data): void {
    }
    public function getMimetype(): string {
        return 'application/json';
    }
    public function fopen(string $mode) {
        return null;
    }
    public function getPermissions(): int {
        return 31;
    }
    public function isUpdateable(): bool {
        return true;
    }
    public function isCreatable(): bool {
        return true;
    }
    public function isDeletable(): bool {
        return true;
    }
    public function getMTime(): int {
        return 1700000000;
    }
    public function getSize() {
        return strlen($this->json);
    }
    public function move(string $targetPath) {
        return null;
    }
    public function getParent() {
        return null;
    }
}

/** Minimal Folder stand-in; see BenchFile for why these are hand-written. */
final class BenchFolder implements Folder {
    private ?BenchFolder $parent = null;

    /** @param array<string, File|Folder> $children */
    public function __construct(private string $path, private array $children) {
        // Back-link children so the index route can reach a page's {slug}.json,
        // which sits in the PARENT of the page's own folder.
        foreach ($this->children as $child) {
            if ($child instanceof self) {
                $child->parent = $this;
            }
        }
    }

    public function getParent() {
        return $this->parent;
    }

    public function getName(): string {
        return basename($this->path);
    }
    public function getPath(): string {
        return $this->path;
    }
    public function getType(): int {
        return FileInfo::TYPE_FOLDER;
    }
    public function getDirectoryListing(): array {
        return array_values($this->children);
    }
    public function nodeExists(string $path): bool {
        return isset($this->children[$path]);
    }
    public function get(string $path) {
        if (isset($this->children[$path])) {
            return $this->children[$path];
        }
        // Resolve a nested relative path too ('en/news/about'), which is how
        // the index route reaches a folder from a stored absolute path.
        if (str_contains($path, '/')) {
            [$head, $rest] = explode('/', $path, 2);
            if (isset($this->children[$head]) && $this->children[$head] instanceof self) {
                return $this->children[$head]->get($rest);
            }
        }
        throw new \OCP\Files\NotFoundException($this->path . '/' . $path);
    }
    public function newFolder(string $path) {
        return new self($this->path . '/' . $path, []);
    }
    public function newFile(string $path, $content = null) {
        return null;
    }
    public function getId(): int {
        return abs(crc32($this->path));
    }
    public function getPermissions(): int {
        return 31;
    }
    public function isUpdateable(): bool {
        return true;
    }
    public function isCreatable(): bool {
        return true;
    }
    public function isDeletable(): bool {
        return true;
    }
    public function getMTime(): int {
        return 1700000000;
    }
    public function getSize() {
        return 0;
    }
    public function move(string $targetPath) {
        return null;
    }
}

class PageLookupBenchmark extends TestCase {

    /** Reads and decodes performed against the fake tree. */
    private int $fileReads = 0;
    private int $jsonDecodes = 0;

    /** uniqueId => ['path' => folder holding the JSON, 'language' => code]. */
    private array $pagePaths = [];

    protected function setUp(): void {
        $this->fileReads = 0;
        $this->jsonDecodes = 0;
        $this->pagePaths = [];
    }

    /**
     * One page file.
     *
     * Hand-written fakes rather than createMock(): a 3000-page x 3-language
     * tree is ~18k nodes, and PHPUnit's generated mocks exhaust a 128 MB
     * limit well before that. These carry only the methods the lookup path
     * touches, and cost a few hundred bytes each.
     */
    private function makePageFile(string $path, string $uniqueId): File {
        return new BenchFile($path, $uniqueId, $this);
    }

    private function makeFolder(string $path, array $children): Folder {
        return new BenchFolder($path, $children);
    }

    /** Called by BenchFile when its content is read. */
    public function countRead(): void {
        $this->fileReads++;
        $this->jsonDecodes++;
    }

    /**
     * A language tree of $pageCount pages, in the canonical IntraVox layout:
     * a {slug}.json beside a {slug}/ folder, nested $depth levels deep.
     *
     * @param string[] &$ids collects the generated uniqueIds, in creation order
     */
    private function makeLanguageTree(string $lang, int $pageCount, array &$ids, int $branching = 10): Folder {
        // A shallow, wide tree like a real intranet (departments -> teams ->
        // pages). $made is the running total so the fixture yields EXACTLY
        // $pageCount pages — the scenarios index into $ids, so an off-by-a-few
        // generator would pick a null id and the run would die rather than
        // measure.
        $made = 0;

        $build = function (string $basePath, int $level) use (
            &$build, &$ids, &$made, $lang, $branching, $pageCount
        ): array {
            $nodes = [];
            for ($i = 0; $i < $branching && $made < $pageCount; $i++) {
                $slug = 'p' . $level . '-' . $i . '-' . $made;
                $uniqueId = 'page-' . $lang . '-' . $made;
                $ids[] = $uniqueId;
                // The index stores the page's own folder ({slug}/), which is
                // what findPageByUniqueId() returns as 'folder' and therefore
                // what the write paths record.
                $this->pagePaths[$uniqueId] = [
                    'path' => $basePath . '/' . $slug,
                    'language' => $lang,
                ];
                $made++;

                // Recurse before closing this level, so pages spread across
                // depth instead of piling into one wide root folder.
                $sub = ($level < 2 && $made < $pageCount)
                    ? $build($basePath . '/' . $slug, $level + 1)
                    : [];

                $nodes[$slug . '.json'] = $this->makePageFile($basePath . '/' . $slug . '.json', $uniqueId);
                $nodes[$slug] = $this->makeFolder($basePath . '/' . $slug, $sub);
            }
            return $nodes;
        };

        $children = $build('/IntraVox/' . $lang, 0);

        // The generator is bounded by branching^3; if the caller asks for more
        // than the shape can hold, say so rather than silently measuring a
        // smaller tree than the label claims.
        if ($made < $pageCount) {
            $this->fail(sprintf(
                'fixture produced %d of %d pages; raise $branching for this size',
                $made, $pageCount
            ));
        }

        return $this->makeFolder('/IntraVox/' . $lang, $children);
    }

    /**
     * @param array<string, array{path:string, language:string}> $indexRows
     *   uniqueId => index row, simulating a warm page index. Empty means the
     *   index knows nothing and every lookup falls back to the tree walk.
     */
    private function makeService(Folder $readFolder, array $allLanguages, array $indexRows = []): PageService {
        $byLang = [];
        foreach ($allLanguages as $l) {
            $byLang[$l->getName()] = $l;
        }
        // Resolves both a bare language name and a nested relative path, so the
        // index route (which resolves a stored path) and the walk route can be
        // measured against the same fixture.
        $base = new BenchFolder('/IntraVox', $byLang);

        $svc = new class extends PageService {
            public function __construct() {
            }
            public function clearCache(): void {
            }
        };

        $user = $this->createMock(\OCP\IUser::class);
        $user->method('getUID')->willReturn('bench');
        $session = $this->createMock(\OCP\IUserSession::class);
        $session->method('getUser')->willReturn($user);
        $config = $this->createMock(\OCP\IConfig::class);
        $config->method('getUserValue')->willReturn('en');
        $languageService = $this->createMock(\OCA\IntraVox\Service\LanguageService::class);
        $languageService->method('isLanguageAvailable')->willReturn(true);
        $languageService->method('getPrimaryLanguage')->willReturn('en');

        // Stands in for the DB-backed index: a hash lookup, which is what a
        // single indexed query amounts to next to a tree walk.
        $index = $this->createMock(\OCA\IntraVox\Service\PageIndexService::class);
        $index->method('findByUniqueId')->willReturnCallback(
            fn(string $uniqueId, ?string $preferred = null) => $indexRows[$uniqueId] ?? null
        );

        $explicit = [
            'userSession' => $session,
            'userId' => 'bench',
            'config' => $config,
            'logger' => $this->createMock(\Psr\Log\LoggerInterface::class),
            'languageService' => $languageService,
            'pageIndexService' => $index,
            'folderContext' => new \OCA\IntraVox\Service\Folder\FolderContext(
                fn() => $base,
                fn(): string => 'en',
                fn(): string => 'en',
                fn(Folder $f): bool => false,
                new \OCA\IntraVox\Service\Language\LanguageResolver(),
                new \OCA\IntraVox\Service\Locator\PageLocator(
                    $this->createMock(\OCA\IntraVox\Service\PageIndexService::class),
                    $this->createMock(\Psr\Log\LoggerInterface::class)
                ),
                fn(): Folder => $readFolder,
                fn(): Folder => $readFolder
            ),
        ];
        foreach ($explicit as $name => $value) {
            (new \ReflectionProperty(PageService::class, $name))->setValue($svc, $value);
        }
        foreach ((new \ReflectionClass(PageService::class))->getProperties() as $prop) {
            if ($prop->isStatic() || isset($explicit[$prop->getName()])) {
                continue;
            }
            $type = $prop->getType();
            if (!$type instanceof \ReflectionNamedType || $type->isBuiltin()) {
                continue;
            }
            if ($prop->isInitialized($svc)) {
                continue;
            }
            $class = $type->getName();
            if (!interface_exists($class) && !class_exists($class)) {
                continue;
            }
            $prop->setValue($svc, $this->doubleOrBuild($class));
        }
        return $svc;
    }

    /**
     * The baseline. Prints a table; asserts only that the walk happened, so
     * this never fails the suite on a slow machine.
     */
    public function testBaselineLookupCost(): void {
        $pagesPerLanguage = (int)(getenv('IVOX_BENCH_PAGES') ?: 1000);
        $languages = ['en', 'de', 'nl'];

        // Three levels of branching b hold b + b^2 + b^3 pages; pick b so the
        // requested size fits.
        $branching = max(4, (int)ceil(pow($pagesPerLanguage, 1 / 3)) + 1);

        $idsByLang = [];
        $folders = [];
        foreach ($languages as $lang) {
            $ids = [];
            $folders[$lang] = $this->makeLanguageTree($lang, $pagesPerLanguage, $ids, $branching);
            $idsByLang[$lang] = $ids;
        }

        $svc = $this->makeService($folders['en'], array_values($folders));

        $scenarios = [
            // Best case: first page of the reader's own language.
            'hit, own language (first)'  => $idsByLang['en'][0],
            // Typical: somewhere in the middle of the own-language tree.
            'hit, own language (middle)' => $idsByLang['en'][(int)floor($pagesPerLanguage / 2)],
            // The case #90/#92 made work — and the one that costs a full
            // own-language walk plus part of another tree.
            'hit, LAST language'         => $idsByLang[end($languages)][(int)floor($pagesPerLanguage / 2)],
            // The worst case, and the one an attacker or a stale link hits.
            'MISS (unknown id)'          => 'page-does-not-exist',
        ];

        // A warm index knows every page; a cold one knows nothing and every
        // lookup falls back to the tree walk. Both run the same scenarios
        // against the same fixture, so the difference is the index alone.
        $warmIndex = $this->pagePaths;

        $rows = [];
        foreach ($scenarios as $label => $uniqueId) {
            $measured = [];
            foreach (['no index' => [], 'indexed' => $warmIndex] as $mode => $indexRows) {
                // Fresh service per run: the request-level caches would
                // otherwise make the second lookup free and hide the real cost.
                $svc = $this->makeService($folders['en'], array_values($folders), $indexRows);
                $this->fileReads = 0;
                $this->jsonDecodes = 0;

                // Reflection rather than a public test hook: the locator is
                // private on purpose, and a benchmark is not a reason to widen
                // production visibility.
                $locate = new \ReflectionMethod(PageService::class, 'locatePageAnyLanguage');
                $readFolder = (new \ReflectionMethod(PageService::class, 'folders'))
                    ->invoke($svc)->readLanguageFolder();

                $start = microtime(true);
                $result = $locate->invoke($svc, $readFolder, $uniqueId);
                $measured[$mode] = [
                    'found' => $result !== null,
                    'reads' => $this->fileReads,
                    'ms' => (microtime(true) - $start) * 1000,
                ];
            }

            // Both routes must agree on whether the page exists: a faster
            // lookup that answers differently is a bug, not an optimisation.
            $this->assertSame(
                $measured['no index']['found'],
                $measured['indexed']['found'],
                sprintf('index and scan disagree on "%s"', $label)
            );

            $rows[] = [
                $label,
                $measured['no index']['found'] ? 'found' : 'not found',
                $measured['no index']['reads'],
                $measured['indexed']['reads'],
                sprintf('%.1f', $measured['no index']['ms']),
                sprintf('%.1f', $measured['indexed']['ms']),
            ];
        }

        $total = $pagesPerLanguage * count($languages);
        fwrite(STDERR, sprintf(
            "\n\n  PAGE LOOKUP — filesystem scan vs page index\n"
            . "  %d pages per language x %d languages = %d pages\n\n",
            $pagesPerLanguage, count($languages), $total
        ));
        fwrite(STDERR, sprintf(
            "  %-28s %-11s %12s %10s %10s %9s\n",
            'scenario', 'result', 'reads:scan', 'indexed', 'ms:scan', 'ms:idx'
        ));
        fwrite(STDERR, '  ' . str_repeat('-', 86) . "\n");
        foreach ($rows as $r) {
            fwrite(STDERR, sprintf("  %-28s %-11s %12s %10s %10s %9s\n", ...$r));
        }
        fwrite(STDERR, "\n  Set IVOX_BENCH_PAGES to change the tree size.\n\n");

        // The only assertion: a miss must have walked every language tree.
        // If this ever stops holding, the lookup changed and the numbers above
        // are no longer comparable to a later run.
        $missReads = (int)$rows[3][2];
        $this->assertGreaterThan(
            $pagesPerLanguage,
            $missReads,
            'a miss should walk beyond a single language tree'
        );
    }

    /**
     * Mock $class, or — when it is final and therefore not doubleable — build a
     * real one and recurse for its own final dependencies (PageShapeSanitizer
     * takes three final leaf sanitizers).
     */
    private function doubleOrBuild(string $class): object {
        try {
            return $this->createMock($class);
        } catch (\PHPUnit\Framework\MockObject\Generator\ClassIsFinalException $e) {
            $ctor = (new \ReflectionClass($class))->getConstructor();
            $args = [];
            foreach ($ctor?->getParameters() ?? [] as $param) {
                $pType = $param->getType();
                $args[] = $pType instanceof \ReflectionNamedType && !$pType->isBuiltin()
                    ? $this->doubleOrBuild($pType->getName())
                    : null;
            }
            return new $class(...$args);
        }
    }

}
