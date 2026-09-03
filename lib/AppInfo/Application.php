<?php
declare(strict_types=1);

namespace OCA\IntraVox\AppInfo;

use OCA\IntraVox\Activity\Provider as ActivityProvider;
use OCA\IntraVox\Activity\Setting as ActivitySetting;
use OCA\IntraVox\Command\SetupCommand;
use OCA\IntraVox\Command\AddDemoFieldsCommand;
use OCA\IntraVox\Command\DebugShareCommand;
use OCA\IntraVox\Listener\CacheCleanupListener;
use OCA\IntraVox\Listener\CommentsEntityListener;
use OCA\IntraVox\Listener\GroupMembershipChangedListener;
use OCA\IntraVox\Listener\UserDeletedListener;
use OCA\IntraVox\Search\PageSearchProvider;
use OCA\IntraVox\Search\UserSearchProvider;
use OCP\AppFramework\App;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\AppFramework\Bootstrap\IBootstrap;
use OCP\AppFramework\Bootstrap\IRegistrationContext;
use OCP\Comments\CommentsEntityEvent;
use OCP\Files\Cache\CacheEntryRemovedEvent;
use OCP\Group\Events\UserAddedEvent;
use OCP\Group\Events\UserRemovedEvent;
use OCP\User\Events\UserDeletedEvent;

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
        // Register search providers
        $context->registerSearchProvider(PageSearchProvider::class);
        $context->registerSearchProvider(UserSearchProvider::class);

        // Register Comments Entity Listener (enables comments on IntraVox pages)
        $context->registerEventListener(
            CommentsEntityEvent::class,
            CommentsEntityListener::class
        );

        // Comment cleanup: fires when a file leaves the filecache for good
        // (trashbin emptied), not when it is moved to the trashbin — so a
        // restored page keeps its comments.
        $context->registerEventListener(
            CacheEntryRemovedEvent::class,
            CacheCleanupListener::class
        );

        // GDPR: cleanup user data when Nextcloud user is deleted
        $context->registerEventListener(
            UserDeletedEvent::class,
            UserDeletedListener::class
        );

        // Cache invalidation: tree- and path-map caches are keyed by group
        // membership; flush them when a user's groups change so peers don't
        // keep seeing stale data until TTL expires.
        $context->registerEventListener(
            UserAddedEvent::class,
            GroupMembershipChangedListener::class
        );
        $context->registerEventListener(
            UserRemovedEvent::class,
            GroupMembershipChangedListener::class
        );


        // Register Activity Provider and Setting for Nextcloud Activity integration
        // Note: These methods may not be available in all Nextcloud versions
        if (method_exists($context, 'registerActivityProvider')) {
            $context->registerActivityProvider(ActivityProvider::class);
        }
        if (method_exists($context, 'registerActivitySetting')) {
            $context->registerActivitySetting(ActivitySetting::class);
        }

        // Register OCC commands
        $context->registerService(SetupCommand::class, function ($c) {
            return new SetupCommand(
                $c->get(\OCA\IntraVox\Service\SetupService::class),
                $c->get(\OCA\IntraVox\Service\DemoDataService::class)
            );
        });

        $context->registerService(AddDemoFieldsCommand::class, function ($c) {
            return new AddDemoFieldsCommand(
                $c->get(\OCP\IUserManager::class),
                $c->get(\OCP\Accounts\IAccountManager::class),
                $c->get(\OCP\IConfig::class)
            );
        });

        $context->registerService(DebugShareCommand::class, function ($c) {
            return new DebugShareCommand(
                $c->get(\OCA\IntraVox\Service\PublicShareService::class)
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
                $c->get(\OCP\ICacheFactory::class),
                $c->get(\OCP\IUserManager::class),
                $c->get(\OCP\IDBConnection::class),
                $c->get(\OCP\App\IAppManager::class),
                $c->get(\OCP\IUserSession::class)->getUser()?->getUID()
            );
        });

        // Register SystemFileService
        $context->registerService(\OCA\IntraVox\Service\SystemFileService::class, function ($c) {
            return new \OCA\IntraVox\Service\SystemFileService(
                $c->get(\OCP\Files\IRootFolder::class),
                $c->get(\OCA\IntraVox\Service\SetupService::class),
                $c->get(\Psr\Log\LoggerInterface::class),
                $c->get(\OCA\IntraVox\Service\LanguageService::class),
                $c->get(\OCP\ICacheFactory::class)
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
                $c->get(\OCA\IntraVox\Service\LanguageService::class),
                $c->get(\OCA\IntraVox\Service\Sanitize\HtmlSanitizer::class),
                $c->get(\OCP\IUserSession::class)->getUser()?->getUID()
            );
        });
    }

    public function boot(IBootContext $context): void {
        $container = $context->getServerContainer();

        // There used to be a late-binding dance here: LanguageService needed
        // PageService to flush caches on a language toggle, while PageService
        // needs LanguageService on every URL lookup, so neither could be
        // constructor-injected. That cycle is gone — LanguageService now
        // injects PageCacheService directly, because the caches have an owner.
        //
        // What remains is one-way: PagePathHelper is a pure helper with no DI,
        // so we sync its language-code set once per request. PageService is no
        // longer resolved here at all.
        try {
            $languageService = $container->get(\OCA\IntraVox\Service\LanguageService::class);

            \OCA\IntraVox\Service\Path\PagePathHelper::setKnownLanguages(
                $languageService->getDiscoveredLanguages()
            );
        } catch (\Throwable $e) {
            // Boot-time errors must not break the whole app; path parsing
            // falls back to its built-in language list until next request.
            $container->get(\Psr\Log\LoggerInterface::class)->warning(
                '[IntraVox] LanguageService boot wiring failed: ' . $e->getMessage()
            );
        }

        // MetaVox's filesplugin.js used to be injected here so its tab would
        // register into a mock Files sidebar that IntraVox built. That route is
        // gone: Nextcloud 34 removed the global `OCA.Files.Sidebar` the plugin
        // registers against, leaving an empty tab rather than a working one.
        // IntraVox now renders MetaVox fields itself from MetaVox's OCS API
        // (see MetaVoxPanel.vue), which is a MetaVox API rather than a
        // Nextcloud one and so survives Nextcloud upgrades. Injecting the
        // plugin here is therefore no longer needed — and dropping it saves a
        // script and a stylesheet on every page load where MetaVox is present.
    }
}
