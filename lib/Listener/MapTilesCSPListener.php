<?php
declare(strict_types=1);

namespace OCA\IntraVox\Listener;

use OCA\IntraVox\AppInfo\Application;
use OCP\AppFramework\Http\EmptyContentSecurityPolicy;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\IConfig;
use OCP\Security\CSP\AddContentSecurityPolicyEvent;

/**
 * Allow the Photo Story maps to load their basemap.
 *
 * Nextcloud's default policy is `img-src 'self' data: blob:`, so every tile
 * fetched from a third-party server is blocked. The symptom is deceptive: the
 * Leaflet canvas, the markers and the zoom maths all work, only the basemap
 * imagery never paints — a grey rectangle with correctly-placed pins.
 *
 * Until now the tiles rendered only by accident, on instances where the
 * *photos* app happened to be enabled: its own CSP listener contributes
 * `https://*.tile.openstreetmap.org` globally. That wildcard requires a
 * subdomain and therefore does NOT match the bare `tile.openstreetmap.org`
 * host IntraVox actually requests (see PhotoStoryController::mapSettings()),
 * so even with photos enabled the default tile URL stays blocked. Relying on
 * another app's policy was wrong regardless; IntraVox declares its own here.
 *
 * The origin is derived from the configured `photostory.tiles.url` instead of
 * being hardcoded, because admins may point the widget at a self-hosted tile
 * server; a hardcoded OSM entry would silently break exactly those setups.
 * Only the scheme+host+port is granted — never the path, and never a wildcard
 * we did not derive from configuration.
 *
 * The lightbox's mini-map is a second, unrelated blockage with the same cause:
 * PhotoLightbox embeds openstreetmap.org/export/embed.html in an iframe, which
 * needs frame-src. That host is NOT derivable from the tile URL — it is
 * `www.openstreetmap.org`, not the tile server — and it is deliberately not
 * folded into the `video_domains` whitelist, which exists so admins can govern
 * *video* embeds and whose emptied state must keep meaning "no video embeds".
 */
final class MapTilesCSPListener implements IEventListener {

    /**
     * Origin of the lightbox mini-map iframe (PhotoLightbox::osmEmbedUrl).
     */
    private const OSM_EMBED_ORIGIN = 'https://www.openstreetmap.org';

    public function __construct(
        private IConfig $config,
    ) {
    }

    /**
     * The type is deliberately NOT narrowed via @implements: the dispatcher
     * hands us a bare Event, so the runtime guard below is what actually
     * protects us, exactly as in the app's other listeners.
     */
    public function handle(Event $event): void {
        if (!($event instanceof AddContentSecurityPolicyEvent)) {
            return;
        }

        // A disabled map never renders a tile layer, so it needs no exemption.
        $enabled = $this->config->getAppValue(
            Application::APP_ID,
            'photostory.map.enabled',
            'yes',
        ) !== 'no';
        if (!$enabled) {
            return;
        }

        $origin = $this->tileOrigin();
        if ($origin === null) {
            return;
        }

        // EmptyContentSecurityPolicy contributes only the directives we set
        // here; ContentSecurityPolicy would re-assert core's own defaults.
        $csp = new EmptyContentSecurityPolicy();
        $csp->addAllowedImageDomain($origin);

        // The lightbox mini-map iframe. PhotoLightbox builds this URL
        // client-side against a hardcoded host with no appconfig behind it,
        // so unlike the tile origin there is nothing here to derive.
        $csp->addAllowedFrameDomain(self::OSM_EMBED_ORIGIN);

        $event->addPolicy($csp);
    }

    /**
     * Reduce the configured tile URL to the scheme://host[:port] a CSP accepts.
     *
     * The stored value is a Leaflet template ('…/{z}/{x}/{y}.png'); the braces
     * live in the path, so parse_url() reads the authority fine. Anything that
     * is not an absolute http(s) URL yields null and we add no directive at
     * all, rather than emitting a malformed source that would weaken or break
     * the whole img-src list.
     */
    private function tileOrigin(): ?string {
        $url = trim($this->config->getAppValue(
            Application::APP_ID,
            'photostory.tiles.url',
            'https://tile.openstreetmap.org/{z}/{x}/{y}.png',
        ));
        if ($url === '') {
            return null;
        }

        $parts = parse_url($url);
        if ($parts === false) {
            return null;
        }

        $scheme = strtolower((string)($parts['scheme'] ?? ''));
        $host = (string)($parts['host'] ?? '');
        if (($scheme !== 'https' && $scheme !== 'http') || $host === '') {
            return null;
        }

        $origin = $scheme . '://' . $host;
        if (isset($parts['port'])) {
            $origin .= ':' . $parts['port'];
        }

        return $origin;
    }
}
