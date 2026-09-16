<?php
declare(strict_types=1);

namespace OCA\IntraVox\Controller;

use OCA\IntraVox\Exception\ForbiddenException;
use OCA\IntraVox\Exception\PageNotFoundException;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\DataResponse;
use OCP\IRequest;
use Psr\Log\LoggerInterface;

/**
 * Linking pages across languages.
 *
 * Split out of ApiController (PR-A). Five routes, each calling the TRANSLATE
 * domain services directly (fase-9): the two reads and link/unlink go to
 * TranslationQueryService, createTranslation to the COMPOSE service
 * (PageCompositionService), and the group semantics to TranslationGroupService.
 * This controller no longer touches PageService.
 *
 * link/unlink/createTranslation each need two things the query/compose services
 * take as per-call closures rather than owning, because both are shared across a
 * domain boundary: the group-writer (writeTranslationGroup — also used by the
 * compose-domain createTranslation) and the cache invalidation. The retired
 * PageService delegators supplied them as $this-bound closures; they are
 * reproduced here from the injected collaborators — writeTranslationGroup() below
 * is byte-identical to PageService's private one (languageOfFolder, else the
 * user's own language, then TranslationGroupService::writeGroup), and clearCache
 * is PageCacheInvalidator::invalidate (the exact fan-out PageService::clearCache
 * delegated to). createTranslation's createPage closure binds to PageWriteService,
 * as TEMPLATE's createPageFromTemplate does — the #70 preflight rides on it.
 *
 * The ACL boundary lives in those services rather than here, and is worth knowing
 * when reading these endpoints: group membership comes from the index, but
 * readability comes from the mount the caller owns. A translation the caller
 * may not read must not leak its title through a listing.
 */
class TranslationApiController extends Controller {
    use ApiErrorTrait;

    public function __construct(
        string $appName,
        IRequest $request,
        // getPage comes from the READ-domain service (fase-4 C6).
        private \OCA\IntraVox\Service\Read\PageReadService $pageRead,
        private \OCA\IntraVox\Service\Translation\TranslationQueryService $translationQuery,
        private \OCA\IntraVox\Service\Compose\PageCompositionService $composition,
        private \OCA\IntraVox\Service\Translation\TranslationGroupService $translationGroups,
        private \OCA\IntraVox\Service\Folder\FolderContext $folders,
        private \OCA\IntraVox\Service\Cache\PageCacheInvalidator $cacheInvalidator,
        private \OCA\IntraVox\Service\Write\PageWriteService $pageWrite,
        private LoggerInterface $logger,
    ) {
        parent::__construct($appName, $request);
    }

    protected function getLogger(): LoggerInterface {
        return $this->logger;
    }

    /**
     * Write a translation group into a page file and its index row — byte-identical
     * to the private PageService::writeTranslationGroup the retired delegators used
     * as a $this-bound closure. The language is where the page actually sits, else
     * the caller's own language (matching PageService's getUserLanguage fallback).
     *
     * @param array $result findPageByUniqueId()-shaped result
     */
    private function writeTranslationGroup(array $result, string $group): void {
        $language = $this->folders->languageOfFolder($result['folder']) ?? $this->folders->userLanguage();
        $this->translationGroups->writeGroup($result, $group, $language);
    }
    /**
     * Link a page to another language version of itself.
     *
     * Both pages end up in one translation group. Symmetric: neither becomes
     * the "source", so removing one language later shrinks the group instead
     * of orphaning the other.
     *
     */
    #[NoAdminRequired]
    public function linkTranslation(string $pageId, ?string $targetUniqueId = null): DataResponse {
        try {
            if (!is_string($targetUniqueId) || $targetUniqueId === '') {
                return new DataResponse(
                    ['error' => 'targetUniqueId is required'],
                    Http::STATUS_BAD_REQUEST
                );
            }

            $group = $this->translationQuery->linkTranslation(
                $pageId,
                $targetUniqueId,
                function (array $result, string $group): void {
                    $this->writeTranslationGroup($result, $group);
                },
                function (): void {
                    $this->cacheInvalidator->invalidate();
                }
            );
            return new DataResponse([
                'success' => true,
                'translationGroup' => $group,
                'translations' => $this->pageRead->getPage($pageId)['translations'] ?? [],
            ]);
        } catch (PageNotFoundException $e) {
            return new DataResponse(['error' => $e->getMessage()], Http::STATUS_NOT_FOUND);
        } catch (ForbiddenException $e) {
            return new DataResponse(['error' => $e->getMessage()], Http::STATUS_FORBIDDEN);
        } catch (\InvalidArgumentException $e) {
            return new DataResponse(['error' => $e->getMessage()], Http::STATUS_BAD_REQUEST);
        } catch (\Exception $e) {
            return new DataResponse(['error' => $e->getMessage()], Http::STATUS_INTERNAL_SERVER_ERROR);
        }
    }
    /**
     * Detach a page from its translation group.
     *
     * Acts on this page only — the other language versions stay linked to each
     * other. Nothing is inferred or re-linked afterwards.
     *
     */
    #[NoAdminRequired]
    public function unlinkTranslation(string $pageId): DataResponse {
        try {
            $this->translationQuery->unlinkTranslation(
                $pageId,
                function (array $result, string $group): void {
                    $this->writeTranslationGroup($result, $group);
                },
                function (): void {
                    $this->cacheInvalidator->invalidate();
                }
            );
            return new DataResponse(['success' => true, 'translations' => []]);
        } catch (PageNotFoundException $e) {
            return new DataResponse(['error' => $e->getMessage()], Http::STATUS_NOT_FOUND);
        } catch (ForbiddenException $e) {
            return new DataResponse(['error' => $e->getMessage()], Http::STATUS_FORBIDDEN);
        } catch (\Exception $e) {
            return new DataResponse(['error' => $e->getMessage()], Http::STATUS_INTERNAL_SERVER_ERROR);
        }
    }
    /**
     * Pages in OTHER languages that this page could be linked to.
     *
     * Powers the editor's "add translation" picker. Excludes the page's own
     * language — a group holds one page per language — and pages already in a
     * group with something else, so linking cannot silently steal a page out of
     * an existing set.
     *
     */
    #[NoAdminRequired]
    public function getTranslationCandidates(string $pageId, ?string $language = null): DataResponse {
        try {
            $candidates = $this->translationQuery->getTranslationCandidates($pageId, $language);
            return new DataResponse(['candidates' => $candidates]);
        } catch (PageNotFoundException $e) {
            return new DataResponse(['error' => $e->getMessage()], Http::STATUS_NOT_FOUND);
        } catch (\Exception $e) {
            return new DataResponse(['error' => $e->getMessage()], Http::STATUS_INTERNAL_SERVER_ERROR);
        }
    }
    /**
     * Create this page in another language, as a linked draft.
     *
     * The entry point editors actually reach for — "make this page in German" —
     * rather than creating a blank page elsewhere and linking it afterwards.
     *
     */
    #[NoAdminRequired]
    public function createTranslation(
        string $pageId,
        ?string $language = null,
        ?string $title = null
    ): DataResponse {
        try {
            if (!is_string($language) || $language === '') {
                return new DataResponse(['error' => 'language is required'], Http::STATUS_BAD_REQUEST);
            }

            $created = $this->composition->createTranslation(
                $pageId,
                $language,
                $title,
                fn(array $data, ?string $parentPath = null): array => $this->pageWrite->createPage($data, $parentPath),
                function (array $result, string $group): void {
                    $this->writeTranslationGroup($result, $group);
                }
            );
            return new DataResponse([
                'success' => true,
                'page' => $created,
                'translations' => $this->pageRead->getPage($pageId)['translations'] ?? [],
            ], Http::STATUS_CREATED);
        } catch (PageNotFoundException $e) {
            return new DataResponse(['error' => $e->getMessage()], Http::STATUS_NOT_FOUND);
        } catch (ForbiddenException $e) {
            return new DataResponse(['error' => $e->getMessage()], Http::STATUS_FORBIDDEN);
        } catch (\InvalidArgumentException $e) {
            return new DataResponse(['error' => $e->getMessage()], Http::STATUS_BAD_REQUEST);
        } catch (\Exception $e) {
            return new DataResponse(['error' => $e->getMessage()], Http::STATUS_INTERNAL_SERVER_ERROR);
        }
    }
    /**
     * Languages this page could still be created in.
     *
     * Excludes the page's own language and any language that already holds a
     * version of it, so the "add translation" control only ever offers a
     * choice that will succeed.
     *
     */
    #[NoAdminRequired]
    public function getTranslatableLanguages(string $pageId): DataResponse {
        try {
            return new DataResponse([
                'languages' => $this->translationQuery->getTranslatableLanguages($pageId),
            ]);
        } catch (PageNotFoundException $e) {
            return new DataResponse(['error' => $e->getMessage()], Http::STATUS_NOT_FOUND);
        } catch (\Exception $e) {
            return new DataResponse(['error' => $e->getMessage()], Http::STATUS_INTERNAL_SERVER_ERROR);
        }
    }
}
