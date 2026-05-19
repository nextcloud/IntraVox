<?php
declare(strict_types=1);

namespace OCA\IntraVox\BackgroundJob;

use OCA\IntraVox\AppInfo\Application;
use OCA\IntraVox\Service\NavigationService;
use OCA\IntraVox\Service\PageService;
use OCA\IntraVox\Service\PermissionService;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\TimedJob;
use Psr\Log\LoggerInterface;

/**
 * Pre-warms IntraVox caches so the first user of the day doesn't pay the
 * cold-cache cost on the homepage/navigation render.
 *
 * The distributed caches we maintain (intravox-pages tree + content,
 * intravox-permissions path-map) are wiped on every page mutation, so
 * post-deploy or post-mutation periods have a few hundred ms of cold
 * latency until the first reader rebuilds them. For enterprise instances
 * with thousands of users hitting `/apps/intravox/` simultaneously after
 * a deploy, that "first reader" becomes a thundering herd.
 *
 * This job runs every 15 minutes and forces a rebuild for each supported
 * language by walking the same code paths as a real request:
 * - PermissionService::buildPagePathMap → caches per-language path map
 * - PageService::getPageTree → caches per-group tree
 *
 * Both of those store their result via distributed cache, so subsequent
 * real users see warm hits regardless of which front-end node serves
 * their request.
 *
 * Marked TIME_INSENSITIVE so it doesn't compete with user-facing cron
 * windows.
 */
class CacheWarmupJob extends TimedJob {
    private const INTERVAL_MINUTES = 15;
    private const LANGUAGES = ['nl', 'en', 'de', 'fr'];

    public function __construct(
        ITimeFactory $time,
        private PageService $pageService,
        private PermissionService $permissionService,
        private NavigationService $navigationService,
        private LoggerInterface $logger,
    ) {
        parent::__construct($time);
        $this->setInterval(self::INTERVAL_MINUTES * 60);
        $this->setTimeSensitivity(self::TIME_INSENSITIVE);
    }

    protected function run($argument): void {
        $warmed = [];
        $errors = [];

        foreach (self::LANGUAGES as $lang) {
            try {
                $this->permissionService->buildPagePathMap($lang);
                $this->pageService->getPageTree(null, $lang);
                $this->navigationService->getNavigation($lang);
                $warmed[] = $lang;
            } catch (\Throwable $e) {
                // Failure to warm one language must not block the rest —
                // a missing or empty language folder is normal on instances
                // that aren't multi-lingual.
                $errors[$lang] = $e->getMessage();
            }
        }

        $this->logger->info('[IntraVox] CacheWarmupJob ran', [
            'warmed' => $warmed,
            'errors' => $errors,
        ]);
    }
}
