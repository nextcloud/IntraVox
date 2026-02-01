<?php
declare(strict_types=1);

namespace OCA\IntraVox\Controller;

use OCA\IntraVox\Constants;
use OCA\IntraVox\Service\PageService;
use OCA\IntraVox\Service\PublicShareService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\Attribute\PublicPage;
use OCP\AppFramework\Http\Attribute\AnonRateThrottle;
use OCP\AppFramework\Http\Attribute\BruteForceProtection;
use OCP\AppFramework\Http\ContentSecurityPolicy;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Http\RedirectResponse;
use OCP\IConfig;
use OCP\IRequest;
use OCP\ISession;
use OCP\IURLGenerator;
use OCP\IUserSession;
use OCP\Security\Bruteforce\IThrottler;
use OCP\Util;
use Psr\Log\LoggerInterface;

class PageController extends Controller {
    private PageService $pageService;
    private PublicShareService $publicShareService;
    private LoggerInterface $logger;
    private IConfig $config;
    private IUserSession $userSession;
    private IThrottler $throttler;
    private ISession $session;
    private IURLGenerator $urlGenerator;

    public function __construct(
        string $appName,
        IRequest $request,
        PageService $pageService,
        PublicShareService $publicShareService,
        LoggerInterface $logger,
        IConfig $config,
        IUserSession $userSession,
        IThrottler $throttler,
        ISession $session,
        IURLGenerator $urlGenerator
    ) {
        parent::__construct($appName, $request);
        $this->pageService = $pageService;
        $this->publicShareService = $publicShareService;
        $this->logger = $logger;
        $this->config = $config;
        $this->userSession = $userSession;
        $this->throttler = $throttler;
        $this->session = $session;
        $this->urlGenerator = $urlGenerator;
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
     * Main index page.
     *
     * Supports optional ?share= parameter for anonymous access via NC share links.
     * When a valid share token is provided, the page is rendered for anonymous users.
     * The hash fragment (#page-{uniqueId}) determines which page to show.
     *
     * @PublicPage
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    #[PublicPage]
    #[NoAdminRequired]
    #[NoCSRFRequired]
    #[AnonRateThrottle(limit: 60, period: 60)]
    #[BruteForceProtection(action: 'intravox_share_access')]
    public function index(): TemplateResponse {
        // Check if this is a share access request
        // Parse share token from query string (NC routing doesn't always populate $_GET)
        $shareToken = null;
        $queryString = $_SERVER['QUERY_STRING'] ?? '';
        if (preg_match('/share=([a-zA-Z0-9]+)/', $queryString, $matches)) {
            $shareToken = $matches[1];
        }
        // Fallback to other methods
        if ($shareToken === null) {
            $shareToken = $_GET['share'] ?? $this->request->getParam('share') ?? null;
        }

        $isAnonymous = $this->userSession->getUser() === null;
        $isShareAccess = $shareToken !== null && $shareToken !== '';

        $this->logger->debug('[PageController] index() called', [
            'shareToken' => $shareToken,
            'isAnonymous' => $isAnonymous,
            'isShareAccess' => $isShareAccess,
            'queryString' => $queryString,
        ]);

        // If share token is provided, validate it (for both anonymous and logged-in users)
        if ($isShareAccess) {
            // Validate the share token format first (cheap check)
            if (!$this->isValidShareTokenFormat($shareToken)) {
                if ($isAnonymous) {
                    $this->registerBruteForceAttempt();
                    return $this->buildPublicNotFoundResponse();
                }
                // For logged-in users with invalid token, just ignore the token
                $isShareAccess = false;
                $shareToken = null;
            }

            // NC sharing must be enabled
            if ($isShareAccess) {
                $ncAllowsLinks = $this->config->getAppValue('core', 'shareapi_allow_links', 'yes') === 'yes';
                if (!$ncAllowsLinks) {
                    if ($isAnonymous) {
                        return $this->buildPublicNotFoundResponse();
                    }
                    $isShareAccess = false;
                    $shareToken = null;
                }
            }

            // Token will be validated by the Vue.js app when it fetches page data
            // The app will call the API with the share token for actual validation
        }

        Util::addScript('intravox', 'intravox-main');
        Util::addStyle('intravox', 'main');

        // Render as public only for anonymous users
        $renderAs = ($isAnonymous && $isShareAccess)
            ? TemplateResponse::RENDER_AS_PUBLIC
            : TemplateResponse::RENDER_AS_USER;

        $response = new TemplateResponse('intravox', 'main', [
            'isPublicShare' => $isShareAccess,
            'shareToken' => $shareToken,
        ], $renderAs);

        $response->setContentSecurityPolicy($this->buildContentSecurityPolicy());

        // Security headers for public access
        if ($isAnonymous && $shareToken !== null) {
            $response->addHeader('Referrer-Policy', 'no-referrer');
            $response->addHeader('X-Frame-Options', 'SAMEORIGIN');
            $response->addHeader('X-Content-Type-Options', 'nosniff');
        }

        // Disable caching for development
        $response->addHeader('Cache-Control', 'no-cache, no-store, must-revalidate');
        $response->addHeader('Pragma', 'no-cache');
        $response->addHeader('Expires', '0');

        return $response;
    }

    /**
     * Share access via path parameter /s/{shareToken}
     *
     * This route is used for anonymous access via NC share links.
     * The share token is passed as a path parameter to avoid query string issues.
     *
     * @PublicPage
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    #[PublicPage]
    #[NoAdminRequired]
    #[NoCSRFRequired]
    #[AnonRateThrottle(limit: 60, period: 60)]
    #[BruteForceProtection(action: 'intravox_share_access')]
    public function shareAccess(string $shareToken): TemplateResponse {
        $isAnonymous = $this->userSession->getUser() === null;

        $this->logger->debug('[PageController] shareAccess() called', [
            'shareToken' => substr($shareToken, 0, 8) . '...',
            'isAnonymous' => $isAnonymous,
        ]);

        // Validate the share token format
        if (!$this->isValidShareTokenFormat($shareToken)) {
            $this->registerBruteForceAttempt();
            return $this->buildPublicNotFoundResponse();
        }

        // NC sharing must be enabled
        $ncAllowsLinks = $this->config->getAppValue('core', 'shareapi_allow_links', 'yes') === 'yes';
        if (!$ncAllowsLinks) {
            return $this->buildPublicNotFoundResponse();
        }

        // Check if share requires a password
        if ($this->publicShareService->shareRequiresPassword($shareToken)) {
            $sessionKey = 'intravox_share_pw_' . $shareToken;
            $sessionPassword = $this->session->get($sessionKey);

            if ($sessionPassword === null || $sessionPassword === '') {
                // No password in session — show password challenge page
                return $this->buildPasswordChallengeResponse($shareToken);
            }

            // Verify the stored session password is still valid
            if (!$this->publicShareService->checkSharePassword($shareToken, $sessionPassword)) {
                // Password in session is no longer valid (share password was changed)
                $this->session->remove($sessionKey);
                return $this->buildPasswordChallengeResponse($shareToken);
            }
        }

        Util::addScript('intravox', 'intravox-main');
        Util::addStyle('intravox', 'main');

        $renderAs = $isAnonymous
            ? TemplateResponse::RENDER_AS_PUBLIC
            : TemplateResponse::RENDER_AS_USER;

        $response = new TemplateResponse('intravox', 'main', [
            'isPublicShare' => true,
            'shareToken' => $shareToken,
        ], $renderAs);

        $response->setContentSecurityPolicy($this->buildContentSecurityPolicy());

        // Security headers for public access
        $response->addHeader('Referrer-Policy', 'no-referrer');
        $response->addHeader('X-Frame-Options', 'SAMEORIGIN');
        $response->addHeader('X-Content-Type-Options', 'nosniff');
        $response->addHeader('Cache-Control', 'no-cache, no-store, must-revalidate');
        $response->addHeader('Pragma', 'no-cache');
        $response->addHeader('Expires', '0');

        return $response;
    }

    /**
     * Authenticate for a password-protected share.
     *
     * Receives the password via POST, verifies it, and stores it in the session.
     * On success, redirects back to the share page.
     */
    #[PublicPage]
    #[NoAdminRequired]
    #[NoCSRFRequired]
    #[AnonRateThrottle(limit: 10, period: 60)]
    #[BruteForceProtection(action: 'intravox_share_password')]
    public function shareAuthenticate(string $shareToken): TemplateResponse|RedirectResponse {
        // Validate the share token format
        if (!$this->isValidShareTokenFormat($shareToken)) {
            $this->registerBruteForceAttempt();
            return $this->buildPublicNotFoundResponse();
        }

        $password = $this->request->getParam('password', '');

        if ($password === '' || !$this->publicShareService->checkSharePassword($shareToken, $password)) {
            // Wrong password — register brute force attempt and show error
            $this->throttler->registerAttempt(
                'intravox_share_password',
                $this->request->getRemoteAddress()
            );
            usleep(random_int(100000, 300000)); // 100-300ms delay on failure
            return $this->buildPasswordChallengeResponse($shareToken, true);
        }

        // Password correct — store in session
        $this->session->set('intravox_share_pw_' . $shareToken, $password);

        // Preserve query string (e.g., ?page=xxx)
        $queryString = $this->request->getParam('returnQuery', '');
        $redirectUrl = $this->urlGenerator->linkToRoute('intravox.page.shareAccess', ['shareToken' => $shareToken]);
        if ($queryString !== '') {
            $redirectUrl .= '?' . $queryString;
        }

        return new RedirectResponse($redirectUrl);
    }

    /**
     * Validate share token format (NC share tokens are typically 15-20 alphanumeric chars).
     */
    private function isValidShareTokenFormat(?string $token): bool {
        if ($token === null || $token === '') {
            return false;
        }
        // NC share tokens are alphanumeric, typically 15-20 chars
        return strlen($token) >= 10 && strlen($token) <= 32 && ctype_alnum($token);
    }

    /**
     * Register a failed attempt for brute force protection.
     */
    private function registerBruteForceAttempt(): void {
        $this->throttler->registerAttempt(
            'intravox_share_access',
            $this->request->getRemoteAddress()
        );
    }

    /**
     * Build a "not found" response for public access.
     */
    private function buildPublicNotFoundResponse(): TemplateResponse {
        // Add random delay to mask timing differences
        usleep(random_int(10000, 50000)); // 10-50ms

        Util::addScript('intravox', 'intravox-main');
        Util::addStyle('intravox', 'main');

        $response = new TemplateResponse(
            'intravox',
            'public-not-found',
            [],
            TemplateResponse::RENDER_AS_PUBLIC
        );

        $response->addHeader('Referrer-Policy', 'no-referrer');
        $response->setStatus(Http::STATUS_NOT_FOUND);

        return $response;
    }

    /**
     * Build a password challenge response for password-protected shares.
     */
    private function buildPasswordChallengeResponse(string $shareToken, bool $wrongPassword = false): TemplateResponse {
        Util::addStyle('intravox', 'main');

        $response = new TemplateResponse(
            'intravox',
            'public-password',
            [
                'shareToken' => $shareToken,
                'wrongPassword' => $wrongPassword,
                'actionUrl' => $this->urlGenerator->linkToRoute('intravox.page.shareAuthenticate', ['shareToken' => $shareToken]),
            ],
            TemplateResponse::RENDER_AS_PUBLIC
        );

        $response->addHeader('Referrer-Policy', 'no-referrer');
        $response->addHeader('X-Frame-Options', 'SAMEORIGIN');
        $response->addHeader('X-Content-Type-Options', 'nosniff');
        $response->addHeader('Cache-Control', 'no-cache, no-store, must-revalidate');

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
