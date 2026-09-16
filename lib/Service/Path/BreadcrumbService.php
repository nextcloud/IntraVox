<?php
declare(strict_types=1);

namespace OCA\IntraVox\Service\Path;

use OCA\IntraVox\Service\Folder\FolderContext;
use OCA\IntraVox\Service\Homepage\HomepageResolverService;
use OCA\IntraVox\Service\LanguageService;
use OCA\IntraVox\Service\Listing\PageLister;
use OCA\IntraVox\Service\Read\PageReadService;

/**
 * The BREADCRUMB domain — gathers the inputs a breadcrumb needs and hands them to
 * the pure BreadcrumbBuilder. Carved verbatim out of PageService::getBreadcrumb
 * (fase-6 Track 1): the page comes from PageReadService, the language + navigation
 * folder from FolderContext, the homepage-pointer flag from HomepageResolverService,
 * and the parent-page lookup from PageLister — all DI-injected, so this service is
 * PageService-free.
 *
 * BreadcrumbServiceTest pins the behaviour (the always-present Home entry, its
 * navigation.json label, the homepage short-circuit, the language-segment skip, the
 * humanised-folder fallback and the current-page marking) byte-for-byte with the
 * former PageService::getBreadcrumb.
 */
final class BreadcrumbService {

    public function __construct(
        private PageReadService $pageRead,
        private FolderContext $folders,
        private HomepageResolverService $homepageResolver,
        private LanguageService $languageService,
        private PageLister $pageLister,
    ) {
    }

    /**
     * Build the breadcrumb trail for a page.
     *
     * @return list<array<string,mixed>>
     */
    public function build(string $pageId): array {
        $page = $this->pageRead->getPage($pageId);
        $language = $this->folders->userLanguage();

        // The configured-homepage pointer check, evaluated here so the builder
        // stays free of PageService seams (it keeps the same !empty() guard).
        $isHomepagePointer = !empty($page['uniqueId'])
            && $this->homepageResolver->isHomepage((string)$page['uniqueId'], $language);

        $readFolder = null;
        try {
            $readFolder = $this->folders->readLanguageFolder();
        } catch (\Exception $e) {
            // no folder — builder falls back to the 'Home' label
        }

        return (new BreadcrumbBuilder($this->languageService))->build(
            $pageId,
            $page,
            $language,
            $isHomepagePointer,
            $readFolder,
            fn(string $folderPath): ?array => $this->pageLister->byFolderPath($folderPath)
        );
    }
}
