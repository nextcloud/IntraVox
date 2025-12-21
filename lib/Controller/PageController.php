<?php
declare(strict_types=1);

namespace OCA\IntraVox\Controller;

use OCA\IntraVox\Constants;
use OCA\IntraVox\Service\PageService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\ContentSecurityPolicy;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\IConfig;
use OCP\IRequest;
use OCP\Util;
use Psr\Log\LoggerInterface;

class PageController extends Controller {
    private PageService $pageService;
    private LoggerInterface $logger;
    private IConfig $config;

    public function __construct(
        string $appName,
        IRequest $request,
        PageService $pageService,
        LoggerInterface $logger,
        IConfig $config
    ) {
        parent::__construct($appName, $request);
        $this->pageService = $pageService;
        $this->logger = $logger;
        $this->config = $config;
    }

    /**
     * Build CSP with video domain whitelist
     */
    private function buildContentSecurityPolicy(): ContentSecurityPolicy {
        $csp = new ContentSecurityPolicy();
        $csp->addAllowedScriptDomain('\'self\'');
        $csp->addAllowedScriptDomain('\'unsafe-eval\'');
        $csp->addAllowedFrameDomain('\'self\'');

        // Add whitelisted video domains from config
        $domains = $this->config->getAppValue(
            'intravox',
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

        foreach ($decoded as $domain) {
            $csp->addAllowedFrameDomain($domain);
        }

        return $csp;
    }

    /**
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function index(): TemplateResponse {
        Util::addScript('intravox', 'intravox-main');
        Util::addStyle('intravox', 'main');

        $response = new TemplateResponse('intravox', 'main');
        $response->setContentSecurityPolicy($this->buildContentSecurityPolicy());

        // Disable caching for development
        $response->addHeader('Cache-Control', 'no-cache, no-store, must-revalidate');
        $response->addHeader('Pragma', 'no-cache');
        $response->addHeader('Expires', '0');

        return $response;
    }

    /**
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function show(string $id): TemplateResponse {
        Util::addScript('intravox', 'intravox-main');
        Util::addStyle('intravox', 'main');

        $response = new TemplateResponse('intravox', 'main');
        $response->setContentSecurityPolicy($this->buildContentSecurityPolicy());

        return $response;
    }

    /**
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function languagePage(string $language, string $pageId): TemplateResponse {
        // This route handles URLs like /en/home
        // Return the same template as index - Vue.js will handle routing client-side
        Util::addScript('intravox', 'intravox-main');
        Util::addStyle('intravox', 'main');

        $response = new TemplateResponse('intravox', 'main');
        $response->setContentSecurityPolicy($this->buildContentSecurityPolicy());

        return $response;
    }

    /**
     * Show page by unique ID
     *
     * Note: @PublicPage removed for security - all pages require Nextcloud authentication.
     * Public sharing feature can be implemented in future with explicit isPublic flag validation.
     *
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function showByUniqueId(string $uniqueId): TemplateResponse {
        Util::addScript('intravox', 'intravox-main');
        Util::addStyle('intravox', 'main');

        // Try to load page by uniqueId to get metadata
        // getPage() supports direct uniqueId lookup (no need to list all pages first)
        $pageData = null;
        $pageTitle = 'IntraVox';

        try {
            $pageData = $this->pageService->getPage($uniqueId);
            if ($pageData && isset($pageData['title'])) {
                $pageTitle = $pageData['title'] . ' - IntraVox';
            }
        } catch (\Exception $e) {
            // Page not found - silently fall back to default title
        }

        $response = new TemplateResponse('intravox', 'main', [
            'pageData' => $pageData,
            'uniqueId' => $uniqueId
        ]);

        // Set page title
        $response->setParams(['pageTitle' => $pageTitle]);
        $response->setContentSecurityPolicy($this->buildContentSecurityPolicy());

        return $response;
    }
}
