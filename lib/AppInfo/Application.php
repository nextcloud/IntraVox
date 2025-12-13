<?php
declare(strict_types=1);

namespace OCA\IntraVox\AppInfo;

use OCA\IntraVox\Command\SetupCommand;
use OCA\IntraVox\Listener\CommentsEntityListener;
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
    }

    public function register(IRegistrationContext $context): void {
        // Register search provider
        $context->registerSearchProvider(PageSearchProvider::class);

        // Register Comments Entity Listener (enables comments on IntraVox pages)
        $context->registerEventListener(
            CommentsEntityEvent::class,
            CommentsEntityListener::class
        );

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

        // Register FooterService
        $context->registerService(\OCA\IntraVox\Service\FooterService::class, function ($c) {
            return new \OCA\IntraVox\Service\FooterService(
                $c->get(\OCP\Files\IRootFolder::class),
                $c->get(\OCP\IUserSession::class),
                $c->get(\OCA\IntraVox\Service\SetupService::class),
                $c->get(\OCP\IConfig::class),
                $c->get(\OCP\IUserSession::class)->getUser()?->getUID()
            );
        });
    }

    public function boot(IBootContext $context): void {
        // Load MetaVox scripts if installed (for IntraVox sidebar integration)
        // MetaVox's filesplugin.js has its own duplicate registration check,
        // so loading it here is safe even if MetaVox also loads it in Files app.
        $appManager = $context->getServerContainer()->get(\OCP\App\IAppManager::class);
        if ($appManager->isInstalled('metavox') && $appManager->isEnabledForUser('metavox')) {
            // Load the main filesplugin that contains the sidebar tab component
            \OCP\Util::addScript('metavox', 'filesplugin');
            \OCP\Util::addStyle('metavox', 'files');
        }
    }
}
