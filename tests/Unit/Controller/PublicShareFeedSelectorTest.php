<?php
declare(strict_types=1);

namespace OCA\IntraVox\Tests\Unit\Controller;

use OCA\IntraVox\Controller\PublicShareController;
use OCA\IntraVox\Service\CalendarService;
use OCA\IntraVox\Service\FeedReaderService;
use OCA\IntraVox\Service\NavigationService;
use OCA\IntraVox\Service\Path\PagePathHelper;
use OCA\IntraVox\Service\Read\PageReadService;
use OCA\IntraVox\Service\PublicShare\ShareBreadcrumbBuilder;
use OCA\IntraVox\Service\PublicShare\ShareMediaServer;
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
 * IV-03: getFeedByShare must not let an anonymous share holder choose the
 * secondary feed selectors (contentType/listId/jiraProject/courseId/
 * moodleForumId). A pinned connectionId still runs with the connection's stored
 * credentials, so an un-published selector would read data the share never
 * exposed. Each selector must match a value the share's feed widgets publish.
 */
class PublicShareFeedSelectorTest extends TestCase {
    private PublicShareService $publicShareService;
    private FeedReaderService $feedReader;

    private function unusedPageRead(): PageReadService {
        return (new \ReflectionClass(PageReadService::class))->newInstanceWithoutConstructor();
    }

    protected function setUp(): void {
        parent::setUp();
        $this->publicShareService = $this->createMock(PublicShareService::class);
        $this->publicShareService->method('isValidShareTokenFormat')->willReturnCallback(
            static fn(?string $t) => $t !== null && strlen($t) >= 10 && strlen($t) <= 32 && ctype_alnum($t)
        );
        $this->publicShareService->method('resolveIntraVoxLinkShare')
            ->willReturn($this->createMock(\OCP\Share\IShare::class));
        $this->publicShareService->method('shareRequiresPassword')->willReturn(false);

        $this->feedReader = $this->createMock(FeedReaderService::class);
    }

    private function controller(IRequest $request): PublicShareController {
        $config = $this->createMock(IConfig::class);
        $config->method('getAppValue')->willReturnCallback(
            static fn(string $app, string $key, $default = null) => match ($key) {
                'shareapi_allow_links' => 'yes',
                default => $default,
            }
        );

        return new PublicShareController(
            'intravox',
            $request,
            $this->unusedPageRead(),
            $this->createMock(SetupService::class),
            $this->publicShareService,
            $this->createMock(SystemFileService::class),
            $this->createMock(NavigationService::class),
            $this->createMock(PermissionService::class),
            $this->createMock(LoggerInterface::class),
            $config,
            $this->createMock(ISession::class),
            $this->createMock(CalendarService::class),
            $this->feedReader,
            new PeopleQuery($this->createMock(UserService::class), $this->createMock(LoggerInterface::class)),
            new ShareBreadcrumbBuilder($this->createMock(SetupService::class)),
            new ShareTreeShaper(),
            new PagePathHelper(),
            new ShareMediaServer(),
            $this->createMock(\OCA\IntraVox\Service\Publication\PublicationStateService::class),
        );
    }

    /** Request carrying a SharePoint connection + an attacker-chosen listId. */
    private function requestWith(array $params): IRequest {
        $request = $this->createMock(IRequest::class);
        $request->method('getParam')->willReturnCallback(
            static fn(string $key, $default = null) => $params[$key] ?? $default
        );
        return $request;
    }

    /** allowedWidgetValues answers per (widgetType,key) from a fixture map. */
    private function publish(array $map): void {
        $this->publicShareService->method('allowedWidgetValues')->willReturnCallback(
            static fn($share, string $type, string $key) => $map[$key] ?? []
        );
    }

    public function testUnpublishedListIdIsRefused(): void {
        $this->publish([
            'connectionId' => ['conn-sp'],
            'contentType' => ['list'],
            'listId' => ['published-list'],
        ]);
        // The connection's credentials must never be used for an un-published list.
        $this->feedReader->expects($this->never())->method('fetchFeed');

        $request = $this->requestWith([
            'sourceType' => 'sharepoint',
            'connectionId' => 'conn-sp',
            'contentType' => 'list',
            'listId' => 'attacker-chosen-list',
        ]);

        $response = $this->controller($request)->getFeedByShare('sometoken12345');

        $this->assertSame(Http::STATUS_FORBIDDEN, $response->getStatus());
    }

    public function testPublishedSelectorsPassThrough(): void {
        $this->publish([
            'connectionId' => ['conn-sp'],
            'contentType' => ['list'],
            'listId' => ['published-list'],
        ]);
        $this->feedReader->expects($this->once())
            ->method('fetchFeed')
            ->willReturn(['items' => []]);

        $request = $this->requestWith([
            'sourceType' => 'sharepoint',
            'connectionId' => 'conn-sp',
            'contentType' => 'list',
            'listId' => 'published-list',
        ]);

        $response = $this->controller($request)->getFeedByShare('sometoken12345');

        $this->assertSame(Http::STATUS_OK, $response->getStatus());
    }

    /**
     * IV-03b: an EMPTY selector the share publishes must be refused, not skipped.
     * Skipping it would drop the filter and let the connector run its broad
     * default query (e.g. Jira: all projects) with the app credentials.
     */
    public function testEmptyPublishedSelectorIsRefused(): void {
        $this->publish([
            'connectionId' => ['conn-jira'],
            'jiraProject' => ['PUB'],
        ]);
        $this->feedReader->expects($this->never())->method('fetchFeed');

        $request = $this->requestWith([
            'sourceType' => 'jira',
            'connectionId' => 'conn-jira',
            'jiraProject' => '', // empty → previously skipped → broad default query
        ]);

        $response = $this->controller($request)->getFeedByShare('sometoken12345');

        $this->assertSame(Http::STATUS_FORBIDDEN, $response->getStatus());
    }

    /**
     * A selector the share does NOT publish stays unconstrained: an empty value
     * is fine and the feed is fetched (no over-restriction).
     */
    public function testEmptyUnpublishedSelectorIsAllowed(): void {
        $this->publish([
            'connectionId' => ['conn-jira'],
            // jiraProject intentionally NOT published → unconstrained
        ]);
        $this->feedReader->expects($this->once())
            ->method('fetchFeed')
            ->willReturn(['items' => []]);

        $request = $this->requestWith([
            'sourceType' => 'jira',
            'connectionId' => 'conn-jira',
            'jiraProject' => '',
        ]);

        $response = $this->controller($request)->getFeedByShare('sometoken12345');

        $this->assertSame(Http::STATUS_OK, $response->getStatus());
    }
}
