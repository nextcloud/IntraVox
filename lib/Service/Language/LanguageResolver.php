<?php

declare(strict_types=1);

namespace OCA\IntraVox\Service\Language;

/**
 * The folder-free half of PageService's language resolution: turning a raw
 * profile-language value into a usable base code, and building the ordered
 * fallback candidate list for the "served language" rule (issue #75).
 *
 * Deliberately stateless and dependency-free — it never touches IConfig, the
 * filesystem or any collaborator. Everything folder-shaped (probing a language
 * folder, the create-on-miss side-effect, the has-real-content check, and the
 * protected getIntraVoxFolder/getLanguageFolder/getReadLanguageFolder seams)
 * stays on PageService, which feeds this class the already-read primitives and
 * keeps the probing loop verbatim. That split is what lets PageService delegate
 * without changing any behaviour: PageLanguageResolutionTest pins both halves.
 *
 * DEFAULT_LANGUAGE is duplicated here (a one-character literal) so the resolver
 * stays free of PageService; PageService keeps its own const for its 50+ other
 * call sites.
 */
final class LanguageResolver {
    public const DEFAULT_LANGUAGE = 'en';

    /**
     * Normalise a raw Nextcloud profile language value to the base code IntraVox
     * addresses folders by. 'nl_NL' -> 'nl'; a value that does not look like a
     * language code (empty, uppercase, digits, too long/short) falls back to the
     * universal default. null is treated as absent — same as an empty value.
     *
     * Mirrors the original getUserLanguage() normalisation exactly: base code via
     * explode('_')[0], then the /^[a-z]{2,3}$/ guard. The caller still owns the
     * logged-out short-circuit and the IConfig read; this is only the transform.
     */
    public function baseLanguageCode(?string $rawProfileLang): string {
        if ($rawProfileLang === null || $rawProfileLang === '') {
            return self::DEFAULT_LANGUAGE;
        }

        // Extract base language code (e.g., 'nl_NL' -> 'nl').
        $langCode = explode('_', $rawProfileLang)[0];

        // Guard against malformed values; fall back to the default language.
        return preg_match('/^[a-z]{2,3}$/', $langCode) ? $langCode : self::DEFAULT_LANGUAGE;
    }

    /**
     * The ordered list of language codes to try when resolving the language a
     * user is actually SHOWN (issue #75): the user's own language first, then the
     * admin "recommended"/primary language if it differs, then English as the
     * universal floor. Order-preserving and de-duplicated.
     *
     * Reproduces resolveEffectiveLanguage()'s candidate construction verbatim:
     * primary is only appended when it differs from the user language, and the
     * default is only appended when not already present. The caller then probes
     * each code against the filesystem in this exact order.
     *
     * @return list<string>
     */
    public function candidateOrder(string $userLang, string $primaryLanguage): array {
        $candidates = [$userLang];

        if ($primaryLanguage !== $userLang) {
            $candidates[] = $primaryLanguage;
        }
        if (!in_array(self::DEFAULT_LANGUAGE, $candidates, true)) {
            $candidates[] = self::DEFAULT_LANGUAGE;
        }

        return $candidates;
    }
}
