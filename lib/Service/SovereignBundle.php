<?php
declare(strict_types=1);

namespace OCA\IntraVox\Service;

use OCP\App\IAppManager;

/** Read and validate the immutable release bundle before making storage changes. */
final class SovereignBundle {
    public function __construct(private IAppManager $appManager) {}

    /** @return array<string,string> Sorted relative paths and exact file bytes. */
    public function files(): array {
        $root = $this->appManager->getAppPath('intravox') . '/demo-data/sovereign-365/en';
        if (!is_dir($root)) {
            throw new \RuntimeException('Gold Master bundle is missing from the installed app');
        }
        $files = [];
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS));
        foreach ($iterator as $file) {
            if ($file->isLink() || !$file->isFile()) {
                throw new \RuntimeException('Unsupported bundle entry');
            }
            $relative = substr($file->getPathname(), strlen($root) + 1);
            $content = file_get_contents($file->getPathname());
            if ($content === false) {
                throw new \RuntimeException('Cannot read bundle file: ' . $relative);
            }
            $this->validate($relative, $content);
            $files[$relative] = $content;
        }
        foreach (['home.json', 'navigation.json', 'footer.json'] as $required) {
            if (!isset($files[$required])) {
                throw new \RuntimeException('Missing required bundle file: ' . $required);
            }
        }
        ksort($files, SORT_STRING);
        return $files;
    }

    private function validate(string $relative, string $content): void {
        if (!preg_match('/\A(?:[a-zA-Z0-9_-]+\/)*[a-zA-Z0-9_.-]+\.(json|svg)\z/D', $relative)
            || preg_match('/\.replit\.dev|localhost|__groupfolders\/1/i', $content)) {
            throw new \RuntimeException('Unsafe Gold Master bundle file: ' . $relative);
        }
        if (str_ends_with($relative, '.json')) {
            json_decode($content, true, 512, JSON_THROW_ON_ERROR);
        }
    }

    /** Length-prefixed paths prevent ambiguous concatenations. @param array<string,string> $files */
    public function hash(array $files): string {
        ksort($files, SORT_STRING);
        $hash = hash_init('sha256');
        foreach ($files as $path => $content) {
            hash_update($hash, strlen($path) . ':' . $path . ':' . hash('sha256', $content) . "\n");
        }
        return hash_final($hash);
    }
}
