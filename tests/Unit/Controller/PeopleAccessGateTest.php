<?php
declare(strict_types=1);

namespace OCA\IntraVox\Tests\Unit\Controller;

use OCA\IntraVox\Controller\PeopleController;
use OCA\IntraVox\Service\PermissionService;
use OCA\IntraVox\Service\UserService;
use OCP\AppFramework\Http;
use OCP\IRequest;
use OCP\IUser;
use OCP\IUserSession;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * IV-People: the People widget endpoints surface the staff directory and go
 * straight to IUserManager (bypassing NC's share-enumeration restrictions), so
 * they must be limited to users with IntraVox access. A logged-in account
 * without access gets 403; a member reaches the service.
 */
class PeopleAccessGateTest extends TestCase {
    private function controller(bool $hasAccess): PeopleController {
        $userService = $this->createMock(UserService::class);
        $userService->method('searchUsers')->willReturn([]);
        $userService->method('getGroups')->willReturn([]);

        $permission = $this->createMock(PermissionService::class);
        $permission->method('hasAccess')->willReturn($hasAccess);

        $session = $this->createMock(IUserSession::class);
        $user = $this->createMock(IUser::class);
        $user->method('getUID')->willReturn('someone');
        $session->method('getUser')->willReturn($user);

        return new PeopleController(
            'intravox',
            $this->createMock(IRequest::class),
            $userService,
            $this->createMock(LoggerInterface::class),
            null,
            null,
            null,
            $permission,
            $session,
        );
    }

    public function testSearchUsersDeniedWithoutIntraVoxAccess(): void {
        $response = $this->controller(false)->searchUsers('anna', 20);
        $this->assertSame(Http::STATUS_FORBIDDEN, $response->getStatus());
    }

    public function testGetGroupsDeniedWithoutIntraVoxAccess(): void {
        $response = $this->controller(false)->getGroups();
        $this->assertSame(Http::STATUS_FORBIDDEN, $response->getStatus());
    }

    public function testSearchUsersAllowedWithIntraVoxAccess(): void {
        $response = $this->controller(true)->searchUsers('anna', 20);
        $this->assertSame(Http::STATUS_OK, $response->getStatus());
    }
}
