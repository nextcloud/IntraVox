<?php
declare(strict_types=1);

namespace OCA\IntraVox\Tests\Integration;

use OCA\IntraVox\Command\SyncSovereignPortalCommand;
use OCA\IntraVox\Service\SetupService;
use OCP\Files\File;
use OCP\Files\IAppData;
use OCP\IConfig;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

/** Runs exclusively inside the guarded, disposable Nextcloud installation. */
final class SovereignPortalSyncTest extends TestCase {
    public function testFilesApiSyncBackupDriftAndIdempotence(): void {
        requireDisposableNextcloud();
        $server = \OC::$server;
        $config = $server->get(IConfig::class);
        $config->deleteAppValue('intravox', 'sovereign_seed_hash');
        $setup = $server->get(SetupService::class);
        $language = $setup->getLanguageFolder('en');
        $home = $language->get('home.json');
        self::assertInstanceOf(File::class, $home);
        $original = $home->getContent();
        $fileId = $home->getId();
        $command = new CommandTester($server->get(SyncSovereignPortalCommand::class));
        self::assertSame(0, $command->execute([], ['interactive' => false]), $command->getDisplay());
        self::assertStringContainsString('Sovereign 365 portal synchronized:', $command->getDisplay());
        self::assertSame($fileId, $language->get('home.json')->getId(), 'Updates must preserve native file IDs');
        self::assertSame('page-3429f149-dd7c-492a-8a4a-dab3ff83d511', json_decode($language->get('home.json')->getContent(), true)['uniqueId']);
        self::assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $config->getAppValue('intravox', 'sovereign_seed_hash'));
        self::assertMatchesRegularExpression('~private snapshot (backups/[^/]+/files.json)~', $command->getDisplay());
        preg_match('~private snapshot (backups/([^/]+)/files.json)~', $command->getDisplay(), $match);
        // Resolve app-scoped appdata via its native factory, not a user-visible folder.
        $private = $server->get(\OC\Files\AppData\Factory::class)->get('intravox');
        self::assertInstanceOf(IAppData::class, $private);
        $snapshot = json_decode($private->getFolder('backups')->getFolder($match[2])->getFile('files.json')->getContent(), true);
        self::assertSame($original, $snapshot['home.json']);
        self::assertFalse($language->nodeExists('backups'));
        $mtime = $language->get('home.json')->getMTime();
        self::assertSame(0, $command->execute([], ['interactive' => false]));
        self::assertStringContainsString('Sovereign 365 portal content already up to date', $command->getDisplay());
        self::assertSame($mtime, $language->get('home.json')->getMTime());
        // Same marker but changed live content must NOT be mistaken for a no-op.
        $language->get('home.json')->putContent('{"title":"Disposable drift"}');
        self::assertSame(0, $command->execute([], ['interactive' => false]));
        self::assertStringContainsString('1 files changed', $command->getDisplay());
        self::assertSame($fileId, $language->get('home.json')->getId());
        self::assertSame(0, $command->execute(['--force' => true], ['interactive' => false]));
        self::assertStringContainsString('private snapshot backups/', $command->getDisplay());
        $output = $this->createMock(\OCP\Migration\IOutput::class);
        $output->expects(self::once())->method('info')->with('Sovereign 365 portal content already up to date');
        $server->get(\OCA\IntraVox\Migration\SyncSovereignPortal::class)->run($output);
    }
}
