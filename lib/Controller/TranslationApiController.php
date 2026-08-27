<?php
declare(strict_types=1);

namespace OCA\IntraVox\Controller;

use OCA\IntraVox\Exception\ForbiddenException;
use OCA\IntraVox\Exception\PageNotFoundException;
use OCA\IntraVox\Service\PageService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\DataResponse;
use OCP\IRequest;
use Psr\Log\LoggerInterface;

/**
 * Linking pages across languages.
 *
 * Split out of ApiController (PR-A). Five routes, one collaborator:
 * PageService, which orchestrates and hands the group semantics to
 * TranslationGroupService (service split, PR-16 and PR-19).
 *
 * The ACL boundary lives down there rather than here, and is worth knowing
 * when reading these endpoints: group membership comes from the index, but
 * readability comes from the mount the caller owns. A translation the caller
 * may not read must not leak its title through a listing.
 */
class TranslationApiController extends Controller {
    use ApiErrorTrait;

    public function __construct(
        string $appName,
        IRequest $request,
        private PageService $pageService,
        private LoggerInterface $logger,
    ) {
        parent::__construct($appName, $request);
    }

    protected function getLogger(): LoggerInterface {
        return $this->logger;
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

            $group = $this->pageService->linkTranslation($pageId, $targetUniqueId);
            return new DataResponse([
                'success' => true,
                'translationGroup' => $group,
                'translations' => $this->pageService->getPage($pageId)['translations'] ?? [],
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
            $this->pageService->unlinkTranslation($pageId);
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
            $candidates = $this->pageService->getTranslationCandidates($pageId, $language);
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

            $created = $this->pageService->createTranslation($pageId, $language, $title);
            return new DataResponse([
                'success' => true,
                'page' => $created,
                'translations' => $this->pageService->getPage($pageId)['translations'] ?? [],
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
                'languages' => $this->pageService->getTranslatableLanguages($pageId),
            ]);
        } catch (PageNotFoundException $e) {
            return new DataResponse(['error' => $e->getMessage()], Http::STATUS_NOT_FOUND);
        } catch (\Exception $e) {
            return new DataResponse(['error' => $e->getMessage()], Http::STATUS_INTERNAL_SERVER_ERROR);
        }
    }
}
