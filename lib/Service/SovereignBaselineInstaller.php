<?php
declare(strict_types=1);

namespace OCA\IntraVox\Service;

use OCA\IntraVox\Service\GroupFolders\GroupFoldersGateway;
use OCP\Files\File;
use OCP\Files\Folder;
use OCP\Files\NotFoundException;

/** Files API writer: preserves file identity/cache and never writes outside the baseline. */
final class SovereignBaselineInstaller {
    public function __construct(private GroupFoldersGateway $groupFolders) {}

    public function install(Folder $languageFolder, int $folderId, bool $overwrite = false): int {
        $homeId = SovereignBaseline::HOME_ID;
        try {
            $home = $languageFolder->get('home.json');
            if ($home instanceof File) {
                $existing = json_decode($home->getContent(), true, 512, JSON_THROW_ON_ERROR);
                $homeId = $existing['uniqueId'] ?? $homeId;
            }
        } catch (NotFoundException $e) {
            // Fresh install uses the canonical id; existing bookmarks keep theirs.
        }
        $executiveFolder = $this->folder($languageFolder, ['sovereign-excom']);
        $this->groupFolders->protectExecutiveFolder($folderId, $executiveFolder->getId());
        $changed = 0;
        foreach ((new SovereignBaseline())->documents($homeId) as $relative => $data) {
            $parts = explode('/', $relative);
            $filename = array_pop($parts);
            $folder = $this->folder($languageFolder, $parts);
            $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR) . "\n";
            try {
                $file = $folder->get($filename);
                if (!$file instanceof File) {
                    throw new \RuntimeException('Baseline target is not a file: ' . $relative);
                }
                if (!$overwrite || $file->getContent() === $json) {
                    continue;
                }
            } catch (NotFoundException $e) {
                $file = $folder->newFile($filename);
            }
            $file->putContent($json);
            $changed++;
        }
        return $changed;
    }

    private function folder(Folder $root, array $parts): Folder {
        foreach ($parts as $part) {
            try {
                $node = $root->get($part);
            } catch (NotFoundException $e) {
                $node = $root->newFolder($part);
            }
            if (!$node instanceof Folder) {
                throw new \RuntimeException('Baseline parent is not a folder');
            }
            $root = $node;
        }
        return $root;
    }
}
