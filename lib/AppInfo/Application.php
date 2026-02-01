<?php
declare(strict_types=1);

namespace OCA\IntraVox\AppInfo;

use OCA\IntraVox\Activity\Provider as ActivityProvider;
use OCA\IntraVox\Activity\Setting as ActivitySetting;
use OCA\IntraVox\Command\SetupCommand;
use OCA\IntraVox\Event\PageDeletedEvent;
use OCA\IntraVox\Listener\CommentsEntityListener;
use OCA\IntraVox\Listener\PageDeletedListener;
use OCA\IntraVox\Search\PageSearchProvider;
use OCP\AppFramework\App;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\AppFramework\Bootstrap\IBootstrap;
use OCP\AppFramework\Bootstrap\IRegistrationContext;
use OCP\Comments\CommentsEntityEvent;

class Application extends App implements IBootstrap {
    public const APP_ID = 'intravox';

    public function __construct(array $urlParams = []) {
        parent::__construct(self::APP_ID, $urlParams);

        // Load composer autoloader for dependencies (e.g., SVG sanitizer)
        $autoloadPath = __DIR__ . '/../../vendor/autoload.php';
        if (file_exists($autoloadPath)) {
            require_once $autoloadPath;
        }
    }

    public function register(IRegistrationContext $context): void {
        // Register search provider
        $context->registerSearchProvider(PageSearchProvider::class);

        // Register Comments Entity Listener (enables comments on IntraVox pages)
        $context->registerEventListener(
            CommentsEntityEvent::class,
            CommentsEntityListener::class
        );

        // Register Page Deleted Listener (cleanup comments when page is deleted)
        $context->registerEventListener(
            PageDeletedEvent::class,
            PageDeletedListener::class
        );

        // Register Activity Provider and Setting for Nextcloud Activity integration
        // Note: These methods may not be available in all Nextcloud versions
        if (method_exists($context, 'registerActivityProvider')) {
            $context->registerActivityProvider(ActivityProvider::class);
        }
        if (method_exists($context, 'registerActivitySetting')) {
            $context->registerActivitySetting(ActivitySetting::class);
        }

        // Register OCC command
        $context->registerService(SetupCommand::class, function ($c) {
            return new SetupCommand(
                $c->get(\OCA\IntraVox\Service\SetupService::class),
                $c->get(\OCA\IntraVox\Service\DemoDataService::class)
            );
        });

        // Register PermissionService
        $context->registerService(\OCA\IntraVox\Service\PermissionService::class, function ($c) {
            return new \OCA\IntraVox\Service\PermissionService(
                $c->get(\OCP\Files\IRootFolder::class),
                $c->get(\OCP\IUserSession::class),
                $c->get(\OCP\IGroupManager::class),
                $c->get(\OCA\IntraVox\Service\SetupService::class),
                $c->get(\OCP\IConfig::class),
                $c->get(\Psr\Log\LoggerInterface::class),
                $c->get(\OCP\IUserSession::class)->getUser()?->getUID()
            );
        });

        // Register SystemFileService
        $context->registerService(\OCA\IntraVox\Service\SystemFileService::class, function ($c) {
            return new \OCA\IntraVox\Service\SystemFileService(
                $c->get(\OCP\Files\IRootFolder::class),
                $c->get(\OCA\IntraVox\Service\SetupService::class),
                $c->get(\Psr\Log\LoggerInterface::class)
            );
        });

        // Register FooterService
        $context->registerService(\OCA\IntraVox\Service\FooterService::class, function ($c) {
            return new \OCA\IntraVox\Service\FooterService(
                $c->get(\OCP\Files\IRootFolder::class),
                $c->get(\OCP\IUserSession::class),
                $c->get(\OCA\IntraVox\Service\SetupService::class),
                $c->get(\OCA\IntraVox\Service\SystemFileService::class),
                $c->get(\OCP\IConfig::class),
                $c->get(\OCP\IUserSession::class)->getUser()?->getUID()
            );
        });
    }

    public function boot(IBootContext $context): void {
        $appManager = $context->getServerContainer()->get(\OCP\App\IAppManager::class);
        $request = $context->getServerContainer()->get(\OCP\IRequest::class);
        $requestUri = $request->getRequestUri();

        // Don't load external scripts on admin/settings pages - OC object is not available there
        $isAdminPage = str_contains($requestUri, '/settings/') || str_contains($requestUri, '/admin/');

        if (!$isAdminPage) {
            // Load MetaVox scripts if installed (for IntraVox sidebar integration)
            // MetaVox's filesplugin.js has its own duplicate registration check,
            // so loading it here is safe even if MetaVox also loads it in Files app.
            if ($appManager->isInstalled('metavox') && $appManager->isEnabledForUser('metavox')) {
                \OCP\Util::addScript('metavox', 'filesplugin');
                \OCP\Util::addStyle('metavox', 'files');
            }
        }
    }
}
