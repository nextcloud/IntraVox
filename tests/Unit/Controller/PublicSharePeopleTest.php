<?php
declare(strict_types=1);

namespace OCA\IntraVox\Tests\Unit\Controller;

use OCA\IntraVox\Controller\PublicShareController;
use OCA\IntraVox\Service\CalendarService;
use OCA\IntraVox\Service\FeedReaderService;
use OCA\IntraVox\Service\NavigationService;
use OCA\IntraVox\Service\PageService;
use OCA\IntraVox\Service\Path\PagePathHelper;
use OCA\IntraVox\Service\PublicShare\ShareBreadcrumbBuilder;
use OCA\IntraVox\Service\PublicShare\ShareTreeShaper;
use OCA\IntraVox\Service\People\PeopleQuery;
use OCA\IntraVox\Service\PermissionService;
use OCA\IntraVox\Service\PublicShareService;
use OCA\IntraVox\Service\SetupService;
use OCA\IntraVox\Service\SystemFileService;
use OCA\IntraVox\Service\UserService;
use OCP\AppFramework\Http;
use OCP\IConfig;
use OCP\IRequest;
use OCP\ISession;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * What the People widget may serve to an anonymous visitor. (F6d)
 *
 * These assertions moved here with the endpoint. They used to construct a
 * PeopleController, because getPeopleByShare() lived there and delegated to
 * getPeople(); now the endpoint sits with the rest of the anonymous surface
 * and runs the query through PeopleQuery, which has no faceted branch at all.
 *
 * The decision under test is unchanged: a facet panel on a share link is a
 * browsable directory of the organisation — roles, buildings, departments and
 * their headcounts — so the endpoint must refuse those parameters itself
 * rather than trusting the frontend not to send them.
 */
class PublicSharePeopleTest extends TestCase {
	private UserService $userService;
	private PublicShareService $publicShareService;

	protected function setUp(): void {
		parent::setUp();
		$this->userService = $this->createMock(UserService::class);
		$this->publicShareService = $this->createMock(PublicShareService::class);
	}

	private function controller(?IRequest $request = null, string $allowPeople = 'no'): PublicShareController {
		$config = $this->createMock(IConfig::class);
		$config->method('getAppValue')->willReturnCallback(
			static fn(string $app, string $key, $default = null) => match ($key) {
				'public_share_allow_people' => $allowPeople,
				'shareapi_allow_links' => 'yes',
				default => $default,
			}
		);

		return new PublicShareController(
			'intravox',
			$request ?? $this->createMock(IRequest::class),
			$this->createMock(PageService::class),
			$this->createMock(SetupService::class),
			$this->publicShareService,
			$this->createMock(SystemFileService::class),
			$this->createMock(NavigationService::class),
			$this->createMock(PermissionService::class),
			$this->createMock(LoggerInterface::class),
			$config,
			$this->createMock(ISession::class),
			$this->createMock(CalendarService::class),
			$this->createMock(FeedReaderService::class),
			new PeopleQuery($this->userService, $this->createMock(LoggerInterface::class)),
			// Real instances, not mocks: pure transforms with nothing worth faking,
			// and a null-returning auto-mock would silently empty the tree.
			new ShareBreadcrumbBuilder($this->createMock(SetupService::class)),
			new ShareTreeShaper(),
			new PagePathHelper(),
		);
	}

	/** A token that passes openShare()'s shape check and resolves to a share. */
	private function openableToken(): string {
		$this->publicShareService->method('resolveIntraVoxLinkShare')
			->willReturn($this->createMock(\OCP\Share\IShare::class));
		// These cases are about the people projection, not the password gate.
		$this->publicShareService->method('shareRequiresPassword')->willReturn(false);

		return 'sometoken12345';
	}

	/**
	 * Decision: no viewer filtering on public shares.
	 *
	 * Before F6d this was enforced by nulling out arguments on the way to
	 * getPeople(). Now the faceted code path is not reachable from here at
	 * all, but the assertion stays: it is the behaviour that matters, not the
	 * mechanism that currently delivers it.
	 */
	public function testPublicShareIgnoresRefineFacetsAndQuery(): void {
		// A request that DOES carry viewer parameters. Without this the mocked
		// IRequest returns null for everything, and the test would pass even
		// if the endpoint forwarded whatever it was given — verified by mutation.
		$request = $this->createMock(IRequest::class);
		$request->method('getParam')->willReturnCallback(
			static fn(string $key, $default = null) => match ($key) {
				'refine' => '[{"field":"role","op":"in","value":["Manager"]}]',
				'facets' => 'role,gebouw',
				'q' => 'jansen',
				'searchFields' => 'displayName',
				default => $default,
			}
		);

		$token = $this->openableToken();

		// The faceted path must never be entered.
		$this->userService->expects($this->never())->method('queryFaceted');
		$this->userService->method('getUsersByFilters')->willReturn(['users' => [], 'total' => 0]);

		$response = $this->controller($request, 'yes')->getPeopleByShare(
			$token,
			null,
			'[{"fieldName":"gebouw","operator":"equals","value":"Noord"}]',
			'AND',
			'displayName',
			'asc',
			50,
			0
		);

		$this->assertSame(Http::STATUS_OK, $response->getStatus());

		$data = $response->getData();
		$this->assertArrayNotHasKey('facets', $data, 'a public share must not return facets');
	}

	/**
	 * By default a public share returns no people at all.
	 *
	 * A share is normally made to hand someone documents; if the page also
	 * carries a People widget, sharing those documents would publish a staff
	 * directory to whoever holds the link. The safe answer has to be the one
	 * that happens when nobody configured anything.
	 */
	public function testPublicShareReturnsNoPeopleByDefault(): void {
		$token = $this->openableToken();
		$this->userService->expects($this->never())->method('getUsersByFilters');

		$response = $this->controller()->getPeopleByShare(
			$token,
			null,
			'[{"fieldName":"gebouw","operator":"equals","value":"Noord"}]'
		);

		$data = $response->getData();
		$this->assertSame(0, $data['total']);
		$this->assertSame([], $data['users']);
	}

	public function testPublicShareServesPeopleOnlyWhenAdminOptedIn(): void {
		$token = $this->openableToken();
		$this->userService->method('getUsersByFilters')->willReturn([
			'users' => [['uid' => 'u1', 'displayName' => 'Anne']],
			'total' => 1,
		]);
		// The page publishes exactly this filter, so the request is in scope.
		$this->publicShareService->method('publishesWidgetValueSet')->willReturn(true);

		$response = $this->controller(null, 'yes')->getPeopleByShare(
			$token,
			null,
			'[{"fieldName":"gebouw","operator":"equals","value":"Noord"}]'
		);

		$data = $response->getData();
		$this->assertSame(1, $data['total']);
		$this->assertCount(1, $data['users']);
		// Even when allowed, viewer filtering stays off on a share.
		$this->assertArrayNotHasKey('facets', $data);
	}

	/**
	 * The hole this closes: opting in published the WIDGET, not the directory.
	 *
	 * Calendar and feed already intersected the request with what the page
	 * configures; people did not. So with public_share_allow_people = yes, anyone
	 * holding any IntraVox share token could hand this endpoint a filter of their
	 * own and read accounts the shared page never displays. The opt-in was doing
	 * the work of an authorisation check, which it was never able to be.
	 */
	public function testAdminOptInDoesNotOpenFiltersThePageDoesNotPublish(): void {
		$token = $this->openableToken();
		$this->userService->expects($this->never())->method('getUsersByFilters');
		// The page publishes some people widget, but not THIS filter.
		$this->publicShareService->method('publishesWidgetValueSet')->willReturn(false);

		$response = $this->controller(null, 'yes')->getPeopleByShare(
			$token,
			null,
			'[{"fieldName":"salaris","operator":"gt","value":"0"}]'
		);

		$data = $response->getData();
		$this->assertSame(0, $data['total']);
		$this->assertSame([], $data['users']);
	}

	/** Manual mode is scoped too: only the uids the widget actually lists. */
	public function testManualSelectionIsLimitedToThePublishedUsers(): void {
		$token = $this->openableToken();
		// Manual mode resolves uids through getUserProfiles(); asserting it is
		// never reached is what makes this test discriminate — without the scope
		// check 'bob' travels all the way into the profile lookup.
		$this->userService->expects($this->never())->method('getUserProfiles');
		$this->publicShareService->method('allowedWidgetValues')->willReturn(['anne']);

		$response = $this->controller(null, 'yes')->getPeopleByShare($token, 'bob');

		$data = $response->getData();
		$this->assertSame(0, $data['total'], 'bob is not published by the widget');
		$this->assertSame([], $data['users']);
	}

	/**
	 * The gap F6d closes: these endpoints used to open a share with
	 * resolveIntraVoxLinkShare() alone, which skips the token-shape check.
	 * A token that cannot possibly be one is now refused before the share
	 * manager is consulted at all.
	 */
	public function testMalformedTokenIsRefusedWithoutTouchingTheShareManager(): void {
		$this->publicShareService->expects($this->never())->method('resolveIntraVoxLinkShare');

		$response = $this->controller()->getPeopleByShare('abc');

		$this->assertSame(Http::STATUS_FORBIDDEN, $response->getStatus());
	}

	/**
	 * The other half of that gap: an admin who turns link sharing off expects
	 * it to be off everywhere, not everywhere except four widget endpoints.
	 */
	public function testLinkSharingDisabledClosesTheWidgetEndpointToo(): void {
		$config = $this->createMock(IConfig::class);
		$config->method('getAppValue')->willReturnCallback(
			static fn(string $app, string $key, $default = null) => match ($key) {
				'shareapi_allow_links' => 'no',
				'public_share_allow_people' => 'yes',
				default => $default,
			}
		);

		$controller = new PublicShareController(
			'intravox',
			$this->createMock(IRequest::class),
			$this->createMock(PageService::class),
			$this->createMock(SetupService::class),
			$this->publicShareService,
			$this->createMock(SystemFileService::class),
			$this->createMock(NavigationService::class),
			$this->createMock(PermissionService::class),
			$this->createMock(LoggerInterface::class),
			$config,
			$this->createMock(ISession::class),
			$this->createMock(CalendarService::class),
			$this->createMock(FeedReaderService::class),
			new PeopleQuery($this->userService, $this->createMock(LoggerInterface::class)),
			// Real instances, not mocks: pure transforms with nothing worth faking,
			// and a null-returning auto-mock would silently empty the tree.
			new ShareBreadcrumbBuilder($this->createMock(SetupService::class)),
			new ShareTreeShaper(),
			new PagePathHelper(),
		);

		$this->publicShareService->expects($this->never())->method('resolveIntraVoxLinkShare');

		$response = $controller->getPeopleByShare('sometoken12345');

		$this->assertSame(Http::STATUS_FORBIDDEN, $response->getStatus());
	}
}
