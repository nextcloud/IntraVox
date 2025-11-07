<?php
declare(strict_types=1);

namespace OCA\IntraVox\AppInfo;

use OCA\IntraVox\Command\SetupCommand;
use OCP\AppFramework\App;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\AppFramework\Bootstrap\IBootstrap;
use OCP\AppFramework\Bootstrap\IRegistrationContext;

class Application extends App implements IBootstrap {
    public const APP_ID = 'intravox';

    public function __construct(array $urlParams = []) {
        parent::__construct(self::APP_ID, $urlParams);
    }

    public function register(IRegistrationContext $context): void {
        // Register OCC command
        $context->registerService(SetupCommand::class, function ($c) {
            return new SetupCommand(
                $c->get(\OCA\IntraVox\Service\SetupService::class)
            );
        });
    }

    public function boot(IBootContext $context): void {
        // Boot method intentionally left minimal to avoid navigation issues
    }
}
