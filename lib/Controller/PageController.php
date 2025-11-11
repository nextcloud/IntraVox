<?php
declare(strict_types=1);

namespace OCA\IntraVox\Controller;

use OCA\IntraVox\Service\PageService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\ContentSecurityPolicy;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\IRequest;
use OCP\Util;
use Psr\Log\LoggerInterface;

class PageController extends Controller {
    private PageService $pageService;
    private LoggerInterface $logger;

    public function __construct(
        string $appName,
        IRequest $request,
        PageService $pageService,
        LoggerInterface $logger
    ) {
        parent::__construct($appName, $request);
        $this->pageService = $pageService;
        $this->logger = $logger;
    }

    /**
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function index(): TemplateResponse {
        Util::addScript('intravox', 'intravox-main');
        Util::addStyle('intravox', 'main');

        $response = new TemplateResponse('intravox', 'main');

        // Set CSP to allow Vue.js to work
        $csp = new ContentSecurityPolicy();
        $csp->addAllowedScriptDomain('\'self\'');
        $csp->addAllowedScriptDomain('\'unsafe-eval\'');
        $response->setContentSecurityPolicy($csp);

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

        // Set CSP to allow Vue.js to work
        $csp = new ContentSecurityPolicy();
        $csp->addAllowedScriptDomain('\'self\'');
        $csp->addAllowedScriptDomain('\'unsafe-eval\'');
        $response->setContentSecurityPolicy($csp);

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

        // Set CSP to allow Vue.js to work
        $csp = new ContentSecurityPolicy();
        $csp->addAllowedScriptDomain('\'self\'');
        $csp->addAllowedScriptDomain('\'unsafe-eval\'');
        $response->setContentSecurityPolicy($csp);

        return $response;
    }

    /**
     * @NoAdminRequired
     * @NoCSRFRequired
     * @PublicPage
     */
    public function showByUniqueId(string $uniqueId): TemplateResponse {
        Util::addScript('intravox', 'intravox-main');
        Util::addStyle('intravox', 'main');

        // Try to load page by uniqueId to get metadata
        $pageData = null;
        $pageTitle = 'IntraVox';

        try {
            // Find page by uniqueId
            $pages = $this->pageService->listPages();
            foreach ($pages as $page) {
                if (isset($page['uniqueId']) && $page['uniqueId'] === $uniqueId) {
                    // Load full page data
                    $pageData = $this->pageService->getPage($page['id']);
                    if ($pageData && isset($pageData['title'])) {
                        $pageTitle = $pageData['title'] . ' - IntraVox';
                    }
                    break;
                }
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

        // Set CSP to allow Vue.js to work
        $csp = new ContentSecurityPolicy();
        $csp->addAllowedScriptDomain('\'self\'');
        $csp->addAllowedScriptDomain('\'unsafe-eval\'');
        $response->setContentSecurityPolicy($csp);

        return $response;
    }
}
