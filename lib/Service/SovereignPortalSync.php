<?php
declare(strict_types=1);

namespace OCA\IntraVox\Service;

use OCA\IntraVox\Service\Cache\PageCacheService;
use OCA\IntraVox\Service\GroupFolders\GroupFoldersGateway;
use OCP\Files\File;
use OCP\Files\Folder;
use OCP\Files\IAppData;
use OCP\IConfig;
use OCP\ICacheFactory;
use OCP\Lock\ILockingProvider;

/** Administrative, explicit synchronization; never called on public page requests. */
final class SovereignPortalSync {
    public function __construct(
        private SovereignBundle $bundle,
        private SetupService $setup,
        private IConfig $config,
        private IAppData $appData,
        private PageCacheService $pageCache,
        private PageIndexService $index,
        private GroupFoldersGateway $groupFolders,
        private ICacheFactory $cacheFactory,
        private ILockingProvider $locks,
    ) {}

    /** @return array{changed:int,hash:string,backup:?string,noop:bool} */
    public function sync(bool $force = false): array {
        $key = 'intravox/sovereign-portal-sync';
        $this->locks->acquireLock($key, ILockingProvider::LOCK_EXCLUSIVE);
        try {
            return $this->synchronize($force);
        } finally {
            $this->locks->releaseLock($key, ILockingProvider::LOCK_EXCLUSIVE);
        }
    }

    /** @return array{changed:int,hash:string,backup:?string,noop:bool} */
    private function synchronize(bool $force): array {
        $files = $this->bundle->files();
        $hash = $this->bundle->hash($files);
        $root = $this->setup->getSharedFolder();
        if (!$root instanceof Folder) {
            throw new \RuntimeException('Configured IntraVox storage is unavailable');
        }
        $language = $this->folder($root, 'en');
        $current = $this->current($language, $files);
        $marker = $this->config->getAppValue('intravox', 'sovereign_seed_hash', '');
        if (!$force && $marker === $hash && $current === $files) {
            return ['changed' => 0, 'hash' => $hash, 'backup' => null, 'noop' => true];
        }
        $backup = $this->backup($current);
        // Establish the real storage ACL before creating any executive documents.
        $executive = $this->folder($language, 'sovereign-excom');
        $this->groupFolders->protectExecutiveFolder($this->setup->getGroupFolderId(), $executive->getId());
        $changed = $this->write($language, $files, $current);
        $this->invalidate();
        // Commit the marker last: a failed write/flush is retried on the next run.
        $this->config->setAppValue('intravox', 'sovereign_seed_hash', $hash);
        return ['changed' => $changed, 'hash' => $hash, 'backup' => $backup, 'noop' => false];
    }

    /** @param array<string,string> $files @return array<string,string> */
    private function current(Folder $root, array $files): array {
        $current = [];
        foreach ($files as $path => $content) {
            if (!$root->nodeExists($path)) {
                continue;
            }
            $file = $root->get($path);
            if (!$file instanceof File) {
                throw new \RuntimeException('Bundle destination is not a file: ' . $path);
            }
            $current[$path] = $file->getContent();
        }
        return $current;
    }

    /** Private appdata snapshot of every existing managed file, including the three root documents. */
    private function backup(array $current): string {
        try {
            $backups = $this->appData->getFolder('backups');
        } catch (\OCP\Files\NotFoundException $e) {
            $backups = $this->appData->newFolder('backups');
        }
        $name = 'sovereign-' . gmdate('Ymd\THis\Z') . '-' . bin2hex(random_bytes(8));
        $snapshot = $backups->newFolder($name);
        // JSON snapshot retains exact bytes, paths and unicode without exposing a public tree.
        $snapshot->newFile('files.json', json_encode($current, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT));
        return 'backups/' . $name . '/files.json';
    }

    private function write(Folder $root, array $files, array $current): int {
        $changed = 0;
        foreach ($files as $path => $content) {
            if (($current[$path] ?? null) === $content) {
                continue;
            }
            $parts = explode('/', $path);
            $name = array_pop($parts);
            $parent = $root;
            foreach ($parts as $part) {
                $parent = $this->folder($parent, $part);
            }
            $file = $parent->nodeExists($name) ? $parent->get($name) : $parent->newFile($name);
            if (!$file instanceof File) {
                throw new \RuntimeException('Bundle destination is not a file: ' . $path);
            }
            $file->putContent($content);
            $changed++;
        }
        return $changed;
    }

    private function folder(Folder $root, string $name): Folder {
        $node = $root->nodeExists($name) ? $root->get($name) : $root->newFolder($name);
        if (!$node instanceof Folder) {
            throw new \RuntimeException('Bundle destination is not a folder: ' . $name);
        }
        return $node;
    }

    private function invalidate(): void {
        $this->pageCache->clearRequest();
        $this->pageCache->clearExpensive();
        $this->index->clearLanguage('en');
        foreach (['intravox-pages', 'intravox-permissions', 'intravox-people'] as $namespace) {
            $this->cacheFactory->createDistributed($namespace)->clear();
        }
    }
}
