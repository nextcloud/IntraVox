<?php
declare(strict_types=1);

namespace OCA\IntraVox\Controller;

use OCA\IntraVox\Constants;
use OCP\AppFramework\Http\ContentSecurityPolicy;
use OCP\IConfig;
use OCP\Util;

/**
 * Emit the IntraVox SPA's assets and content-security policy (Phase 6).
 *
 * PageController renders the app template from five entry points (index, show,
 * showByUniqueId, shareAccess, and the public-not-found page), and each carried a
 * verbatim copy of the webpack asset triple; buildContentSecurityPolicy() was a
 * private method with a config-driven video-domain whitelist. Both live here now,
 * so a render path cannot emit only some of the three bundles (which leaves the
 * SPA half-initialised) and the CSP logic has one home.
 *
 * @property IConfig $config the host controller must expose a $config property
 */
trait RendersAppShell {

    /**
     * Load the three webpack bundles the SPA needs, in order:
     * vendors (node_modules) -> shared (code used by main+admin) -> main. All
     * three must load or the main entry's runtime never fires its mount (blank
     * page, no error). Plus the main stylesheet.
     */
    protected function emitAppShellAssets(): void {
        Util::addScript('intravox', 'intravox-vendors');
        Util::addScript('intravox', 'intravox-shared');
        Util::addScript('intravox', 'intravox-main');
        Util::addStyle('intravox', 'main');
    }

    /**
     * Build the CSP: self scripts/frames plus the admin-configured video-domain
     * whitelist. An explicitly-emptied whitelist blocks all embeds; only a failed
     * JSON decode falls back to the defaults.
     */
    protected function buildContentSecurityPolicy(): ContentSecurityPolicy {
        $csp = new ContentSecurityPolicy();
        $csp->addAllowedScriptDomain('\'self\'');
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
}
