<?php
declare(strict_types=1);

namespace OCA\IntraVox\Tests\Unit\Service\Harness;

use OCP\Files\File;
use OCP\Files\FileInfo;
use OCP\Files\Folder;
use OCP\Files\NotFoundException;

/**
 * Facade-free filesystem-node fixtures (COMMIT 0, fase-10 harness split).
 * makeFile()/makeFolder() build mocked OCP\Files nodes; lifted verbatim out of
 * BuildsPageService so they outlive the PageService facade. Requires a
 * PHPUnit\Framework\TestCase host (createMock).
 */
trait BuildsNodeFixtures {
    /**
     * A File whose getContent() returns the given page JSON. getId() is a stable
     * hash of the path so fixtures that look a page up by file id are consistent.
     */
    protected function makeFile(string $path, array $json): File {
        $file = $this->createMock(File::class);
        $file->method('getName')->willReturn(basename($path));
        $file->method('getType')->willReturn(FileInfo::TYPE_FILE);
        $file->method('getPath')->willReturn($path);
        $file->method('getContent')->willReturn(json_encode($json));
        $file->method('getId')->willReturn(abs(crc32($path)));
        return $file;
    }

    /**
     * A Folder that records move() into $this->moves and can be made read-only /
     * non-creatable / non-deletable so permission preflights are testable.
     *
     * The host class must declare `private array $moves = [];` if it asserts on
     * moves; makeFolder() only writes to it when move() is actually called.
     *
     * @param array<string,\OCP\Files\Node> $children name => node
     */
    protected function makeFolder(
        string $path,
        array $children = [],
        bool $deletable = true,
        bool $creatable = true
    ): Folder {
        $folder = $this->createMock(Folder::class);
        $folder->method('getName')->willReturn(basename($path));
        $folder->method('getType')->willReturn(FileInfo::TYPE_FOLDER);
        $folder->method('getPath')->willReturn($path);
        $folder->method('getDirectoryListing')->willReturn(array_values($children));
        $folder->method('isDeletable')->willReturn($deletable);
        $folder->method('isCreatable')->willReturn($creatable);
        $folder->method('isUpdateable')->willReturn(true);
        $folder->method('nodeExists')->willReturnCallback(
            fn($n) => isset($children[$n])
        );
        $folder->method('get')->willReturnCallback(function ($p) use ($children, $path) {
            if (isset($children[$p])) {
                return $children[$p];
            }
            throw new NotFoundException($path . '/' . $p);
        });
        $folder->method('move')->willReturnCallback(function ($dest) use ($path) {
            if (property_exists($this, 'moves')) {
                $this->moves[$path] = $dest;
            }
        });
        return $folder;
    }
}
