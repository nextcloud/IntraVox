<?php
declare(strict_types=1);

namespace OCA\IntraVox\Service;

use OCP\Util;

/** Load the exact entrypoints emitted by webpack, including split CSS. */
final class AppAssets {
    public static function load(string $entry): void {
        $manifest = self::entries();
        if (!isset($manifest[$entry]) || !is_array($manifest[$entry])) {
            throw new \RuntimeException('IntraVox asset entry is missing. Run npm run build.');
        }
        foreach ($manifest[$entry] as $asset) {
            if (!is_string($asset) || !preg_match('~^(js|css)/(intravox-[a-zA-Z0-9_.-]+)\.(js|css)$~D', $asset, $match)
                || ($match[1] === 'js' ? 'js' : 'css') !== $match[3]) {
                throw new \RuntimeException('Invalid IntraVox asset manifest.');
            }
            if ($match[1] === 'js') {
                Util::addScript('intravox', $match[2]);
            } else {
                Util::addStyle('intravox', $match[2]);
            }
        }
        Util::addStyle('intravox', 'main');
    }

    public static function appPath(): string {
        // Resolve the installed app, not a symlinked source directory. During
        // development the installed bundle may intentionally be a separate copy.
        return class_exists(\OC::class, false)
            ? \OCP\Server::get(\OCP\App\IAppManager::class)->getAppPath('intravox')
            : dirname(__DIR__, 2);
    }

    private static function entries(): array {
        $file = self::appPath() . '/js/entrypoints.json';
        if (!is_file($file)) {
            throw new \RuntimeException('IntraVox asset manifest is missing. Run npm run build.');
        }
        $entries = json_decode((string)file_get_contents($file), true, 32, JSON_THROW_ON_ERROR);
        if (!is_array($entries)) {
            throw new \RuntimeException('Invalid IntraVox asset manifest.');
        }
        return $entries;
    }
}
