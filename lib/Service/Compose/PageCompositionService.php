<?php

declare(strict_types=1);

namespace OCA\IntraVox\Service\Compose;

use OCA\IntraVox\Exception\ForbiddenException;
use OCA\IntraVox\Exception\PageNotFoundException;
use OCA\IntraVox\Service\Folder\FolderContext;
use OCA\IntraVox\Service\Media\PageMediaService;
use OCA\IntraVox\Service\Sanitize\HtmlSanitizer;
use OCA\IntraVox\Service\Template\PageTemplateService;
use OCA\IntraVox\Service\Translation\TranslationGroupService;
use OCA\IntraVox\Service\Util\PageIdUtils;
use OCP\Files\Folder;
use OCP\Files\NotFoundException;
use Psr\Log\LoggerInterface;

/**
 * The COMPOSE domain, carved out of PageService: the four methods that compose a
 * new page FROM an existing one — copyPage, createTranslation, and the
 * template pair (createPageFromTemplate, saveAsTemplate). Their shared verb is
 * "make a new page from an existing artefact", which is why they belong together
 * and not in Template/ (copyPage/createTranslation have nothing to do with
 * templates, and saveAsTemplate runs page->template).
 *
 * The page READ is the injected PageReadService (the #70 per-user recompute rides
 * on it, unchanged); createPage is INJECTED per call as a $this-bound closure so
 * the composition reaches PageService's real create (the #70 isCreatable preflight
 * in the Write service) with zero change — AND the composition tests can drive this
 * service directly with a stub createPage. The engines
 * (template/translation-group/media/html-sanitizer/id utils) + FolderContext +
 * PageReadService + PageCacheInvalidator + PageLocator + PageCacheService are
 * ctor-injected — page lookup, the template read, and findPageFolder (the
 * media-source folder resolve, moved here in fase-7 T6) are all self-sourced from
 * those. Only writeTranslationGroup stays page-lookup-bound and comes in per call
 * as a closure.
 *
 * PageCopyCompositionTest, PageTranslationCompositionTest and PageSlugUniquenessTest
 * pin the behaviour byte-for-byte.
 */
class PageCompositionService {
    public function __construct(
        private PageTemplateService $pageTemplateService,
        private TranslationGroupService $translationGroups,
        private PageMediaService $media,
        private HtmlSanitizer $htmlSanitizer,
        private PageIdUtils $idUtils,
        private FolderContext $folders,
        private string $userId,
        private LoggerInterface $logger,
        private \OCA\IntraVox\Service\Cache\PageCacheInvalidator $cacheInvalidator,
        private \OCA\IntraVox\Service\Read\PageReadService $pageRead,
        private \OCA\IntraVox\Service\Locator\PageLocator $locator,
        private \OCA\IntraVox\Service\Cache\PageCacheService $pageCache,
    ) {
    }

    /**
     * Create a translation of a page into another language (#translations).
     *
     * @param \Closure(array, ?string): array $createPage
     * @param \Closure(array, string): void $writeTranslationGroup
     * @return array the created page
     * @throws PageNotFoundException when the source does not exist
     * @throws \InvalidArgumentException when the target language is invalid,
     *   is the source's own, or already holds a version of this page
     */
    public function createTranslation(
        string $sourceUniqueId,
        string $language,
        ?string $title,
        \Closure $createPage,
        \Closure $writeTranslationGroup
    ): array {
        if (!preg_match('/^[a-z]{2,3}$/', $language)) {
            throw new \InvalidArgumentException('Invalid language code: ' . $language);
        }

        // Self-sourced cross-language root (fase-7), byte-identical to the old
        // rootClosure the delegator built.
        $intraVoxRoot = fn(): \OCP\Files\Folder => $this->folders->intraVox();

        $source = $this->locator->locatePageAnyLanguage($intraVoxRoot, $this->folders->readLanguageFolder(), $sourceUniqueId);
        if ($source === null || !isset($source['file'])) {
            throw new PageNotFoundException('Page not found: ' . $sourceUniqueId);
        }

        $sourceLanguage = $this->folders->languageOfFolder($source['folder']);
        if ($sourceLanguage === $language) {
            throw new \InvalidArgumentException(
                'This page is already in that language.'
            );
        }

        $sourceData = json_decode($source['file']->getContent(), true);
        if (!is_array($sourceData)) {
            throw new \InvalidArgumentException('Could not read the source page');
        }

        // One page per language per group — refuse rather than create a second
        // German version that would make the switcher ambiguous.
        $group = $sourceData['translationGroup'] ?? null;
        if (!empty($group) && $this->translationGroups->groupHasLanguage($group, $language)) {
            throw new \InvalidArgumentException(
                'A version of this page already exists in that language.'
            );
        }

        // The target language folder must exist; creating one silently would
        // add a language to the intranet as a side effect of translating.
        try {
            $targetFolder = $this->folders->intraVox()->get($language);
        } catch (NotFoundException $e) {
            throw new \InvalidArgumentException(
                'That language has no content folder yet. Add the language in the admin settings first.'
            );
        }
        if (!($targetFolder instanceof Folder)) {
            throw new \InvalidArgumentException('Invalid language folder: ' . $language);
        }
        if (!$targetFolder->isCreatable()) {
            throw new ForbiddenException('You do not have permission to create a page in that language');
        }

        // Assign the group up front so both sides land linked in one write
        // each, rather than being linked afterwards as a second step that
        // could half-fail.
        //
        // Known half-state: if createPage() below fails, the SOURCE keeps this
        // fresh group as its only member. That is harmless by construction —
        // resolveTranslations() excludes the page itself, so a singleton group
        // renders nothing — and the next successful link or unlink rewrites it.
        if (empty($group)) {
            $group = $this->translationGroups->newGroupId();
            $writeTranslationGroup($source, $group);
        }

        $pageData = $sourceData;
        unset($pageData['order']);
        $baseTitle = $this->htmlSanitizer->decodeEntitiesRecursive((string)($sourceData['title'] ?? 'Untitled'));
        $pageData['title'] = ($title !== null && $title !== '') ? $title : $baseTitle;
        $pageData['id'] = $this->idUtils->sanitizeId($pageData['title']);
        $pageData['uniqueId'] = 'page-' . $this->idUtils->generateUUID();
        $pageData['translationGroup'] = $group;
        // Draft: an untranslated copy is not something readers should meet.
        $pageData['status'] = 'draft';
        $pageData['created'] = time();
        $pageData['modified'] = time();

        // Mirror the source's position within its own language tree, so the
        // German page sits where the English one does rather than at the root.
        $sourceRelative = $this->folders->relativePathFromRoot($source['folder']);
        $sourceParent = dirname($sourceRelative);
        $parentPath = $language;
        if ($sourceParent !== '.' && $sourceParent !== '') {
            $segments = explode('/', $sourceParent);
            // Swap the language segment for the target language; the rest of
            // the path only exists in the target tree if the parents were
            // translated too, and getOrCreateFolderPath() creates what is missing.
            array_shift($segments);
            $parentPath = $language . (empty($segments) ? '' : '/' . implode('/', $segments));
        }

        $created = $createPage($pageData, $parentPath);

        // A translation starts as a copy of the source, so it needs the
        // source's images too — the same way copyPage does it. Without this the
        // text carried over but every image 404'd, because the JSON stores bare
        // file names that resolve against the page being viewed.
        $this->media->copyPageMedia($source['folder'] ?? null, $this->findPageFolder($created['uniqueId']), 'createTranslation');

        $this->cacheInvalidator->invalidate();

        return $created;
    }

    /**
     * Save a page as a template.
     *
     * @return array Result with success status and template data or error message
     */
    public function saveAsTemplate(
        string $pageUniqueId,
        string $templateTitle,
        ?string $templateDescription
    ): array {
        try {
            // Get the source page
            $pageData = $this->pageRead->getPage($pageUniqueId);
            if (!$pageData) {
                return ['success' => false, 'error' => 'Page not found'];
            }

            // Reserve a collision-free template folder (+_media)
            $langFolder = $this->folders->languageFolder();
            [$templateId, $templateFolder, $templateMediaFolder] =
                $this->pageTemplateService->newTemplateFolder($langFolder, $this->idUtils->sanitizeId($templateTitle));

            // Prepare template data
            $templateData = $pageData;
            $templateData['uniqueId'] = 'template-' . $this->idUtils->generateUUID();
            $templateData['title'] = $templateTitle;
            $templateData['description'] = $templateDescription ?? '';
            $templateData['isTemplate'] = true;
            $templateData['created'] = time();
            $templateData['createdBy'] = $this->userId;
            $templateData['sourcePageId'] = $pageUniqueId;

            // Remove page-specific data
            unset($templateData['path']);
            unset($templateData['parentPath']);

            // Copy media files from source page to template
            $pageFolder = $this->findPageFolder($pageUniqueId);
            if ($pageFolder && $pageFolder->nodeExists('_media')) {
                $sourceMediaFolder = $pageFolder->get('_media');
                if ($sourceMediaFolder instanceof Folder) {
                    $this->media->copyMediaFolderContents($sourceMediaFolder, $templateMediaFolder);
                }
            }

            // Write template JSON
            $jsonFile = $templateFolder->newFile($templateId . '.json');
            $jsonFile->putContent(json_encode($templateData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

            $this->logger->info('Created template: ' . $templateId . ' from page: ' . $pageUniqueId);

            return [
                'success' => true,
                'templateId' => $templateId,
                'template' => [
                    'id' => $templateId,
                    'uniqueId' => $templateData['uniqueId'],
                    'title' => $templateData['title'],
                    'description' => $templateData['description'],
                    'created' => $templateData['created'],
                    'createdBy' => $templateData['createdBy'],
                ],
            ];
        } catch (\Exception $e) {
            $this->logger->error('Failed to save as template: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Create a new page from a template.
     *
     * @param \Closure(array, ?string): array $createPage
     * @return array Result with success status and page data
     */
    public function createPageFromTemplate(
        string $templateId,
        string $pageTitle,
        ?string $parentPath,
        \Closure $createPage
    ): array {
        try {
            // Get template data (inlined from the retired PageService::getTemplate
            // facade, fase-7). Byte-faithful to that facade: ONLY the language-folder
            // resolution degrades to null; a throw from getTemplate() itself is NOT
            // caught here — it propagates to the outer catch below, which surfaces the
            // real $e->getMessage() exactly as the facade did (its getTemplate() call
            // sat outside its own try, so the exception bubbled through the delegator
            // into this same outer catch).
            try {
                $templateLangFolder = $this->folders->languageFolder();
            } catch (\Exception $e) {
                $templateLangFolder = null;
            }
            $templateData = $templateLangFolder === null
                ? null
                : $this->pageTemplateService->getTemplate($templateLangFolder, $templateId);
            if ($templateData === null) {
                return ['success' => false, 'error' => 'Template not found'];
            }

            // Prepare page data from template
            $pageData = $templateData;

            // Generate new page ID and uniqueId
            $pageId = $this->idUtils->sanitizeId($pageTitle);
            $pageData['id'] = $pageId;
            $pageData['title'] = $pageTitle;
            $pageData['uniqueId'] = 'page-' . $this->idUtils->generateUUID();
            $pageData['created'] = time();
            $pageData['modified'] = time();

            // Remove template-specific fields
            unset($pageData['isTemplate']);
            unset($pageData['description']);
            unset($pageData['createdBy']);
            unset($pageData['sourcePageId']);

            // A brand-new page is not a translation of anything, so it must not
            // inherit translationGroup from the template (or the page it was saved
            // from): that would silently attach it to another page's translation
            // set. A real translation is created through the translation flow, which
            // sets the group deliberately and with its own permission check.
            unset($pageData['translationGroup']);

            // New pages from templates always start as draft
            $pageData['status'] = 'draft';

            // Create the page using existing method
            $createdPage = $createPage($pageData, $parentPath);

            // Copy media files from template to new page
            $templatesFolder = $this->pageTemplateService->templatesFolder($this->folders->languageFolder());
            if ($templatesFolder && $templatesFolder->nodeExists($templateId)) {
                $templateFolder = $templatesFolder->get($templateId);
                if ($templateFolder instanceof Folder && $templateFolder->nodeExists('_media')) {
                    $templateMediaFolder = $templateFolder->get('_media');

                    // Get the new page's folder (should be in cache from createPage)
                    $newPageFolder = $this->findPageFolder($createdPage['uniqueId']);
                    $this->logger->info('Template media copy: page folder found = ' . ($newPageFolder ? 'yes' : 'no') . ' for ' . $createdPage['uniqueId']);
                    if ($newPageFolder && $templateMediaFolder instanceof Folder) {
                        // Create _media folder if not exists
                        if (!$newPageFolder->nodeExists('_media')) {
                            $newPageFolder->newFolder('_media');
                        }
                        $pageMediaFolder = $newPageFolder->get('_media');
                        if ($pageMediaFolder instanceof Folder) {
                            $this->media->copyMediaFolderContents($templateMediaFolder, $pageMediaFolder);
                        }
                    }
                }
            }

            $this->logger->info('Created page from template: ' . $templateId . ' -> ' . $createdPage['uniqueId']);

            // Re-fetch through getPage() so the response includes
            // enrichWithPathData (path, breadcrumb info, permissions) and
            // a sanitize pass — the same shape the frontend gets on a
            // normal page load. Without this the editor mounts with a
            // half-populated page and rendered blank until manual save +
            // reload. Falls back to createdPage if the fresh read fails
            // for any reason (e.g. ACL race on a brand-new folder).
            try {
                $fullPage = $this->pageRead->getPage($createdPage['uniqueId']);
            } catch (\Exception $e) {
                $this->logger->warning(
                    '[createPageFromTemplate] getPage failed on freshly created page, falling back to validated data',
                    ['uniqueId' => $createdPage['uniqueId'], 'error' => $e->getMessage()]
                );
                $fullPage = $createdPage;
            }

            return [
                'success' => true,
                'page' => $fullPage,
            ];
        } catch (\Exception $e) {
            $this->logger->error('Failed to create page from template: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Copy a page (its content + media) into a new draft page (issue: copy page).
     *
     * @param \Closure(array, ?string): array $createPage
     * @return array The freshly created page (getPage shape).
     * @throws \Exception When the source cannot be located.
     */
    public function copyPage(
        string $sourceUniqueId,
        ?string $targetParentId,
        ?string $newTitle,
        \Closure $createPage
    ): array {
        $languageFolder = $this->folders->languageFolder();
        // Self-sourced cross-language root (fase-7).
        $intraVoxRoot = fn(): \OCP\Files\Folder => $this->folders->intraVox();

        // A copy follows its source across language folders, like every other
        // operation on an existing page (#90).
        $source = $this->locator->locatePageAnyLanguage($intraVoxRoot, $languageFolder, $sourceUniqueId);
        if ($source === null || !isset($source['file'])) {
            throw new PageNotFoundException('Page not found: ' . $sourceUniqueId);
        }

        $sourceData = json_decode($source['file']->getContent(), true);
        if (!is_array($sourceData)) {
            throw new \Exception('Could not read source page');
        }

        // Determine the destination parent path.
        $parentPath = null;
        if ($targetParentId !== null && $targetParentId !== '') {
            $targetParent = $this->locator->locatePageAnyLanguage($intraVoxRoot, $languageFolder, $targetParentId);
            if ($targetParent === null || !isset($targetParent['folder'])) {
                throw new PageNotFoundException('Target parent not found: ' . $targetParentId);
            }
            // getRelativePathFromRoot() keeps the leading language segment, and
            // getOrCreateFolderPath() honours it, so the copy lands in the
            // target parent's language rather than the copier's.
            $parentPath = $this->folders->relativePathFromRoot($targetParent['folder']);
        } elseif (isset($source['folder'])) {
            // Same parent as the source. For a page at the language ROOT,
            // dirname() yields '.', which used to become null and sent the copy
            // to the reader's own language folder — an English page copied by a
            // German user landed in de/. Fall back to the source's own language
            // root instead, so a copy never changes language.
            $sourceRelPath = $this->folders->relativePathFromRoot($source['folder']);
            $sourceParentPath = dirname($sourceRelPath);
            if ($sourceParentPath === '.' || $sourceParentPath === '') {
                $sourceLanguage = $this->folders->languageOfFolder($source['folder']);
                $parentPath = $sourceLanguage;
            } else {
                $parentPath = $sourceParentPath;
            }
        }

        // Build the copy's page data (fresh identity, draft status).
        // Decode the source title first: it is stored HTML-encoded (sanitizeText),
        // and createPage re-encodes it — without decoding, "Tips &amp; Tricks"
        // would double-encode to "Tips &amp;amp; Tricks (copy)".
        $baseTitle = $this->htmlSanitizer->decodeEntitiesRecursive((string)($sourceData['title'] ?? 'Untitled'));
        $title = $newTitle !== null && $newTitle !== '' ? $newTitle : $baseTitle . ' (copy)';
        $pageData = $sourceData;
        unset($pageData['order']); // never inherit sibling order
        // A copy is a new page, not a translation of the source. Inheriting the
        // group made the copy a same-language member of it, which is the exact
        // state createTranslation() refuses to create because it makes the
        // language switcher ambiguous. createPage() assigns a fresh group.
        unset($pageData['translationGroup']);
        $pageData['id'] = $this->idUtils->sanitizeId($title);
        $pageData['title'] = $title;
        $pageData['uniqueId'] = 'page-' . $this->idUtils->generateUUID();
        $pageData['status'] = 'draft';
        $pageData['created'] = time();
        $pageData['modified'] = time();

        $createdPage = $createPage($pageData, $parentPath);

        // Copy media assets from the source page folder into the copy.
        $this->media->copyPageMedia($source['folder'] ?? null, $this->findPageFolder($createdPage['uniqueId']), 'copyPage');

        $this->cacheInvalidator->invalidate();

        try {
            return $this->pageRead->getPage($createdPage['uniqueId']);
        } catch (\Exception $e) {
            return $createdPage;
        }
    }

    /**
     * Find a page folder by its uniqueId (moved verbatim from PageService in
     * fase-7 T6; the four compose facades were its only callers).
     *
     * Instance-identity is load-bearing: the injected PageCacheService is the
     * same per-request singleton PageService, PageReadService and the write
     * service all share, so the pageFolders map this reads was already populated
     * by createPage's setPageFolder and the cross-language locate below writes
     * back into the one map every service sees.
     *
     * @param string $uniqueId Page uniqueId
     * @return \OCP\Files\Folder|null The page folder or null if not found
     */
    private function findPageFolder(string $uniqueId): ?\OCP\Files\Folder {
        // Check cache first
        if ($this->pageCache->hasPageFolder($uniqueId)) {
            return $this->pageCache->getPageFolder($uniqueId);
        }

        // Self-sourced cross-language root (fase-7), byte-identical to the old
        // rootClosure PageService::locatePageAnyLanguage built.
        $intraVoxRoot = fn(): \OCP\Files\Folder => $this->folders->intraVox();

        try {
            // Follow the page across language folders. This resolves the folder
            // media is copied FROM and TO, and it fails by returning null, which
            // callers treat as "no media" — so on a foreign-language page,
            // "Save as template" and copy-page silently produced a page with no
            // images at all rather than reporting anything (#90 family).
            $result = $this->locator->locatePageAnyLanguage($intraVoxRoot, $this->folders->readLanguageFolder(), $uniqueId);
            if ($result !== null && isset($result['folder'])) {
                $folder = $result['folder'];
                $this->pageCache->setPageFolder($uniqueId, $folder);
                return $folder;
            }
        } catch (\Exception $e) {
            $this->logger->warning('Could not find page folder for: ' . $uniqueId . ' - ' . $e->getMessage());
        }

        return null;
    }
}
