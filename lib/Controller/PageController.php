<?php
declare(strict_types=1);

namespace OCA\IntraVox\Controller;

use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\ContentSecurityPolicy;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\IRequest;
use OCP\Util;

class PageController extends Controller {
    public function __construct(
        string $appName,
        IRequest $request
    ) {
        parent::__construct($appName, $request);
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
}
