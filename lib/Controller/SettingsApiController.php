<?php
declare(strict_types=1);

namespace OCA\IntraVox\Controller;

use OCA\IntraVox\AppInfo\Application;
use OCA\IntraVox\Constants;
use OCA\IntraVox\Service\EngagementSettingsService;
use OCA\IntraVox\Service\PublicationSettingsService;
use OCA\IntraVox\Service\TelemetryService;
use OCA\IntraVox\Service\VideoDomainPolicy;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\DataResponse;
use OCP\IConfig;
use OCP\IGroupManager;
use OCP\IRequest;
use OCP\IUserSession;
use Psr\Log\LoggerInterface;

/**
 * Instance-wide IntraVox settings: video domains, engagement, publication,
 * people-on-public-shares, and telemetry.
 *
 * Split out of ApiController (PR-A). These nine routes share a shape the page
 * endpoints do not: they read and write appconfig, and every setter is
 * admin-only. Note the getters are #[NoAdminRequired] on purpose — the frontend
 * needs to know whether comments are on before it renders, whatever the caller
 * may change.
 *
 * The admin gate is checked IN THE BODY, not by the absence of
 * #[NoAdminRequired]; docs/route-table.md counts those separately as
 * "admin (checked in body)". Both forms are load-bearing here and both moved
 * across unchanged.
 */
class SettingsApiController extends Controller {
    use ApiErrorTrait;
    use ChecksAdminAccess;
    use \OCA\IntraVox\Controller\Shared\SharePathTrait;

    public function __construct(
        string $appName,
        IRequest $request,
        private EngagementSettingsService $engagementSettings,
        private PublicationSettingsService $publicationSettings,
        private TelemetryService $telemetryService,
        private VideoDomainPolicy $videoDomains,
        private IConfig $config,
        private IGroupManager $groupManager,
        private IUserSession $userSession,
        private LoggerInterface $logger,
    ) {
        parent::__construct($appName, $request);
    }

    protected function getLogger(): LoggerInterface {
        return $this->logger;
    }
    /**
     * Get video domain whitelist
     *
     */
    #[NoAdminRequired]
    #[NoCSRFRequired]
    public function getVideoDomains(): DataResponse {
        $domains = $this->config->getAppValue(
            Application::APP_ID,
            'video_domains',
            Constants::getDefaultVideoDomainsJson()
        );

        // Decode the stored JSON
        $decoded = json_decode($domains, true);

        // Only use defaults if JSON decode FAILED (null), not for empty array
        // This allows admins to explicitly block all video embeds by removing all domains
        if ($decoded === null) {
            $decoded = Constants::DEFAULT_VIDEO_DOMAINS;
        }

        return new DataResponse(['domains' => $decoded]);
    }
    /**
     * Set video domain whitelist
     * Warning-based system: all domains allowed, but with category warnings
     * Admin only
     */
    public function setVideoDomains(): DataResponse {
        // Manual admin check since @AuthorizedAdminSetting has dependency issues
        if (!$this->isAdmin()) {
            return new DataResponse([
                'success' => false,
                'message' => 'Only administrators can change video domain settings',
            ], Http::STATUS_FORBIDDEN);
        }

        // Get domains from request - handle both JSON body and form data
        $domains = $this->request->getParam('domains');

        // If domains is null, try parsing JSON body directly
        if ($domains === null) {
            $body = file_get_contents('php://input');
            $data = json_decode($body, true);
            $domains = $data['domains'] ?? [];
            $this->logger->debug('[ApiController] Parsed JSON body for domains: ' . json_encode($domains));
        }

        // Ensure domains is an array
        if (!is_array($domains)) {
            $domains = [];
        }

        // Validate and categorize domains
        $validDomains = [];
        $invalidDomains = [];
        $domainCategories = [];
        $warnings = [];

        foreach ($domains as $domain) {
            $domain = trim($domain);
            if (empty($domain)) {
                continue;
            }

            // Must be HTTPS - this is a hard requirement for security
            if (!str_starts_with($domain, 'https://')) {
                $invalidDomains[] = $domain . ' (HTTPS required for security)';
                continue;
            }

            // Valid URL check
            if (!filter_var($domain, FILTER_VALIDATE_URL)) {
                $invalidDomains[] = $domain . ' (invalid URL format)';
                continue;
            }

            // Get domain category
            $parsedUrl = parse_url($domain);
            $host = $parsedUrl['host'] ?? '';
            $category = $this->videoDomains->categorise($host);

            // Remove trailing slash
            $domain = rtrim($domain, '/');

            $validDomains[] = $domain;
            $domainCategories[$domain] = $category;

            // Add warnings for non-recommended domains
            if ($category['category'] === 'commercial') {
                $warnings[] = $host . ': Commercial platform - consider privacy-friendly alternatives like PeerTube';
            } elseif ($category['category'] === 'discouraged') {
                $warnings[] = $host . ': This platform has significant tracking and privacy concerns. Consider using PeerTube instead.';
            }
        }

        // If there are invalid domains (HTTP or malformed), return error
        if (!empty($invalidDomains)) {
            return new DataResponse([
                'success' => false,
                'message' => 'Some domains are not valid: ' . implode(', ', $invalidDomains),
                'invalidDomains' => $invalidDomains,
            ], Http::STATUS_BAD_REQUEST);
        }

        // Save domains - all valid HTTPS domains are allowed
        $this->config->setAppValue(
            Application::APP_ID,
            'video_domains',
            json_encode(array_unique($validDomains))
        );

        $this->logger->info('[ApiController] Video domains updated: ' . implode(', ', $validDomains));

        // Return success with warnings if applicable
        $response = [
            'success' => true,
            'domains' => $validDomains,
            'categories' => $domainCategories,
        ];

        if (!empty($warnings)) {
            $response['warnings'] = $warnings;
        }

        return new DataResponse($response);
    }
    /**
     * Get engagement settings (reactions & comments)
     *
     */
    #[NoAdminRequired]
    #[NoCSRFRequired]
    public function getEngagementSettings(): DataResponse {
        return new DataResponse($this->engagementSettings->getAll());
    }
    /**
     * Update engagement settings
     * Admin only
     */
    public function setEngagementSettings(): DataResponse {
        // Only admins can change engagement settings
        if (!$this->isAdmin()) {
            return new DataResponse([
                'success' => false,
                'message' => 'Only administrators can change engagement settings',
            ], Http::STATUS_FORBIDDEN);
        }

        // Get settings from request body
        $body = file_get_contents('php://input');
        $settings = json_decode($body, true) ?? [];

        try {
            $updated = $this->engagementSettings->updateAll($settings);

            $this->logger->info('IntraVox Audit: Engagement settings updated by admin', $settings);

            return new DataResponse([
                'success' => true,
                'settings' => $updated,
            ]);
        } catch (\Exception $e) {
            $this->logger->error('[ApiController] Failed to update engagement settings: ' . $e->getMessage());
            return new DataResponse([
                'success' => false,
                'message' => $e->getMessage(),
            ], Http::STATUS_INTERNAL_SERVER_ERROR);
        }
    }
    /**
     * Get publication settings (MetaVox field names for date filtering)
     *
     */
    #[NoAdminRequired]
    #[NoCSRFRequired]
    public function getPublicationSettings(): DataResponse {
        return new DataResponse($this->publicationSettings->getAll());
    }
    /**
     * Update publication settings
     * Admin only
     */
    /**
     * Whether People widgets may render on public share links.
     *
     */
    #[NoAdminRequired]
    #[NoCSRFRequired]
    public function getPublicSharePeopleSetting(): DataResponse {
        return new DataResponse([
            'allowPeopleOnPublicShares' => $this->peopleAllowedOnPublicShares(),
        ]);
    }
    /**
     * Allow or forbid People widgets on public share links.
     *
     * Off by default. A public share is normally created to hand someone
     * documents; if the page also carries a People widget, that act publishes
     * a staff directory to whoever holds the link. Turning this on is a
     * deliberate decision an administrator takes, not a side effect of
     * sharing a folder.
     */
    public function setPublicSharePeopleSetting(): DataResponse {
        if (!$this->isAdmin()) {
            return new DataResponse([
                'success' => false,
                'message' => 'Only administrators can change this setting',
            ], Http::STATUS_FORBIDDEN);
        }

        $body = json_decode((string)file_get_contents('php://input'), true) ?? [];
        $allow = ($body['allowPeopleOnPublicShares'] ?? false) === true;

        $this->config->setAppValue('intravox', 'public_share_allow_people', $allow ? 'yes' : 'no');

        $this->logger->info('[ApiController] People-on-public-shares setting changed', [
            'allowed' => $allow,
        ]);

        return new DataResponse([
            'success' => true,
            'allowPeopleOnPublicShares' => $allow,
        ]);
    }
    public function setPublicationSettings(): DataResponse {
        // Only admins can change publication settings
        if (!$this->isAdmin()) {
            return new DataResponse([
                'success' => false,
                'message' => 'Only administrators can change publication settings',
            ], Http::STATUS_FORBIDDEN);
        }

        // Get settings from request body
        $body = file_get_contents('php://input');
        $settings = json_decode($body, true) ?? [];

        try {
            $updated = $this->publicationSettings->updateAll($settings);

            $this->logger->info('[ApiController] Publication settings updated', $settings);

            return new DataResponse([
                'success' => true,
                'settings' => $updated,
            ]);
        } catch (\Exception $e) {
            $this->logger->error('[ApiController] Failed to update publication settings: ' . $e->getMessage());
            return new DataResponse([
                'success' => false,
                'message' => $e->getMessage(),
            ], Http::STATUS_INTERNAL_SERVER_ERROR);
        }
    }
    /**
     * Set telemetry settings (enable/disable anonymous usage statistics)
     * Admin only
     *
     * @return DataResponse
     */
    public function setTelemetrySettings(): DataResponse {
        // Only admins can change telemetry settings
        if (!$this->isAdmin()) {
            return new DataResponse([
                'success' => false,
                'message' => 'Only administrators can change telemetry settings',
            ], Http::STATUS_FORBIDDEN);
        }

        // Get settings from request body
        $body = file_get_contents('php://input');
        $settings = json_decode($body, true) ?? [];

        try {
            $enabled = isset($settings['enabled']) ? (bool)$settings['enabled'] : false;
            $this->telemetryService->setEnabled($enabled);

            $this->logger->info('[ApiController] Telemetry settings updated', ['enabled' => $enabled]);

            return new DataResponse([
                'success' => true,
                'enabled' => $enabled,
            ]);
        } catch (\Exception $e) {
            $this->logger->error('[ApiController] Failed to update telemetry settings: ' . $e->getMessage());
            return new DataResponse([
                'success' => false,
                'message' => $e->getMessage(),
            ], Http::STATUS_INTERNAL_SERVER_ERROR);
        }
    }
}
