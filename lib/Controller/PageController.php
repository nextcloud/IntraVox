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
}
