<?php
declare(strict_types=1);

namespace OCA\IntraVox\Tests\Unit\Service;

use PHPUnit\Framework\TestCase;

/**
 * Creating a page does not shell out.
 *
 * scanPageFolder() carried a groupfolders branch that ran
 * `php /var/www/nextcloud/occ files:scan --path=...` through exec(), synchronously,
 * on every page create — and then returned unconditionally, so the in-process
 * scanner underneath it was unreachable whenever that branch was taken.
 *
 * Except it never was. The branch matched on getPath(), which is the user-facing
 * view (/rik/files/IntraVox/nl/page); "/__groupfolders/" only ever appears in
 * getInternalPath(), which is precisely what the surviving code matches on. So the
 * fork was dead code and the in-process scanner had been doing the work all along.
 *
 * Verified on dev before removing it: four successful page creations produced zero
 * "Failed to scan page folder" warnings, on a container where the hardcoded
 * /var/www/nextcloud/occ does not exist (it is /var/www/html/occ there). Had the
 * branch been live, each of those four would have logged a failure.
 *
 * This test exists because the failure mode was invisible: hardcoded paths that
 * are wrong on the standard container image, in a branch nothing reaches, quietly
 * doing nothing. A regex against getPath() looks plausible in review.
 */
class PageScanNoForkTest extends TestCase {
    private function source(): string {
        // scanPageFolder moved to Write/PageWriteService when the create body was
        // carved into the AUTHOR domain (god-class dissolution).
        return file_get_contents(__DIR__ . '/../../../lib/Service/Write/PageWriteService.php');
    }

    /**
     * The source with comments removed.
     *
     * These assertions are about what the code DOES, and the comment explaining
     * why the shell-out was removed necessarily names it. Asserting against raw
     * source makes the documentation trip the test — which is how a useful comment
     * ends up deleted to make a test pass.
     */
    private function code(): string {
        return preg_replace(
            ['#/\*.*?\*/#s', '#//[^\n]*#'],
            '',
            $this->source()
        );
    }

    public function testPageServiceNeverForksAProcess(): void {
        $code = $this->code();

        foreach (['exec(', 'proc_open(', 'shell_exec(', 'passthru('] as $call) {
            $this->assertStringNotContainsString(
                $call,
                $code,
                "PageService is on the page-create path; {$call} does not belong there"
            );
        }
    }

    public function testTheOccPathIsNotHardcodedAnywhereInPageService(): void {
        $this->assertStringNotContainsString(
            '/var/www/nextcloud',
            $this->code(),
            'That path is wrong on the official container image, where occ lives in /var/www/html'
        );
    }

    /**
     * The surviving scanner matches on getInternalPath(), not getPath().
     *
     * This is the distinction the removed branch got wrong, so it is worth pinning:
     * whichever value the groupfolders regex is applied to must be the one that can
     * actually contain __groupfolders.
     */
    public function testTheGroupfolderRegexIsAppliedToTheInternalPath(): void {
        $source = $this->code();
        $start = strpos($source, 'private function scanPageFolder(');
        $this->assertNotFalse($start);
        // Bound on the NEXT method, not a byte count: a fixed window runs past the
        // end and starts asserting about whatever follows.
        $next = strpos($source, '    private function ', $start + 20);
        $body = substr($source, $start, $next === false ? 3000 : $next - $start);

        $this->assertStringContainsString('getInternalPath()', $body);
        $this->assertStringContainsString('__groupfolders', $body);
        $this->assertStringNotContainsString(
            'files:scan',
            $body,
            'The occ shell-out must not come back'
        );
    }

    /** The regex only ever matched the internal path — the removed branch could not fire. */
    public function testTheUserFacingPathNeverMatchesTheGroupfolderPattern(): void {
        $removedPattern = '#/__groupfolders/(\d+)/files/(.+)$#';

        foreach (['/rik/files/IntraVox/nl/home', '/admin/files/IntraVox/en/team'] as $userPath) {
            $this->assertSame(
                0,
                preg_match($removedPattern, $userPath),
                'getPath() cannot contain __groupfolders, which is why the branch was dead'
            );
        }

        $this->assertSame(
            1,
            preg_match('#__groupfolders/\d+/(.+)$#', '__groupfolders/3/files/nl/home'),
            'The surviving pattern does match what getInternalPath() returns'
        );
    }
}
