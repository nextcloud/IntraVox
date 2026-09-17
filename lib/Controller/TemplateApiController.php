<?php
declare(strict_types=1);

namespace OCA\IntraVox\Controller;

use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\DataResponse;
use OCP\IRequest;
use Psr\Log\LoggerInterface;

/**
 * Page templates: list, read, save, delete, and create a page from one.
 *
 * Split out of ApiController (PR-A), which carried 57 route methods across a
 * dozen unrelated resources. Templates is the most self-contained of them.
 *
 * The four query/delete routes call the TEMPLATE domain service
 * (PageTemplateService) directly, resolving the language folder through
 * FolderContext (fase-9) — the exact `folders()->languageFolder()` the retired
 * PageService delegators wrapped, including their per-method degrade-to-fallback
 * when it throws. The two composition routes (saveAsTemplate/createPageFromTemplate)
 * call the COMPOSE domain service (PageCompositionService) directly — the same
 * userId-scoped instance the retired PageService::saveAsTemplate /
 * createPageFromTemplate delegators built and forwarded to. createPageFromTemplate's
 * per-call createPage closure now binds to the injected PageWriteService, which is
 * exactly what PageService::createPage delegated to (the #70 isCreatable preflight
 * rides on it, unchanged). This controller no longer touches PageService at all.
 *
 * Method bodies are verbatim. The #[NoAdminRequired] attributes travel with
 * them, because those attributes ARE the authorization posture — see
 * docs/route-table.md, which is a checked fixture precisely so a move like this
 * cannot quietly change who may call what.
 */
class TemplateApiController extends Controller {
    use ApiErrorTrait;

    public function __construct(
        string $appName,
        IRequest $request,
        private LoggerInterface $logger,
        private \OCA\IntraVox\Service\Template\PageTemplateService $templates,
        private \OCA\IntraVox\Service\Folder\FolderContext $folders,
        private \OCA\IntraVox\Service\Compose\PageCompositionService $composition,
        private \OCA\IntraVox\Service\Write\PageWriteService $pageWrite,
    ) {
        parent::__construct($appName, $request);
    }

    protected function getLogger(): LoggerInterface {
        return $this->logger;
    }
    /**
     * List all available page templates
     *
     */
    #[NoAdminRequired]
    public function listTemplates(): DataResponse {
        try {
            // PageService::listTemplates / canCreateTemplates each degraded ONLY a
            // languageFolder() throw to [] / false (their inner catch wrapped just the
            // folder resolution; a throw from the TEMPLATE service itself propagated).
            // Reproduce that exact scoping: catch around the folder resolve, then call
            // the service unguarded so its own errors still surface as a 500.
            try {
                $langFolder = $this->folders->languageFolder();
            } catch (\Exception $e) {
                $langFolder = null;
            }
            $templates = $langFolder !== null ? $this->templates->listTemplates($langFolder) : [];
            $canCreate = $langFolder !== null ? $this->templates->canCreateTemplates($langFolder) : false;

            return new DataResponse([
                'templates' => $templates,
                'canCreate' => $canCreate,
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Failed to list templates: ' . $e->getMessage());
            return new DataResponse([
                'error' => $e->getMessage(),
            ], Http::STATUS_INTERNAL_SERVER_ERROR);
        }
    }
    /**
     * Get a specific template by ID
     *
     */
    #[NoAdminRequired]
    public function getTemplate(string $id): DataResponse {
        try {
            // PageService::getTemplate degraded a languageFolder() throw to null (which
            // the 404 branch below already handles); a throw from the service itself
            // propagated to the 500. Reproduce that scoping.
            try {
                $langFolder = $this->folders->languageFolder();
            } catch (\Exception $e) {
                $langFolder = null;
            }
            $template = $langFolder !== null ? $this->templates->getTemplate($langFolder, $id) : null;

            if ($template === null) {
                return new DataResponse([
                    'error' => 'Template not found',
                ], Http::STATUS_NOT_FOUND);
            }

            return new DataResponse($template);
        } catch (\Exception $e) {
            $this->logger->error('Failed to get template: ' . $e->getMessage());
            return new DataResponse([
                'error' => $e->getMessage(),
            ], Http::STATUS_INTERNAL_SERVER_ERROR);
        }
    }
    /**
     * Save a page as a template
     *
     */
    #[NoAdminRequired]
    public function saveAsTemplate(): DataResponse {
        try {
            $pageUniqueId = $this->request->getParam('pageUniqueId');
            $templateTitle = $this->request->getParam('templateTitle');
            $templateDescription = $this->request->getParam('templateDescription');

            if (!$pageUniqueId || !$templateTitle) {
                return new DataResponse([
                    'error' => 'Missing required parameters: pageUniqueId, templateTitle',
                ], Http::STATUS_BAD_REQUEST);
            }

            // Check if user can create templates. PageService::canCreateTemplates
            // degraded a languageFolder() throw to false (→ 403 here); reproduce it.
            try {
                $langFolder = $this->folders->languageFolder();
                $canCreate = $this->templates->canCreateTemplates($langFolder);
            } catch (\Exception $e) {
                $canCreate = false;
            }
            if (!$canCreate) {
                return new DataResponse([
                    'error' => 'You do not have permission to create templates',
                ], Http::STATUS_FORBIDDEN);
            }

            $result = $this->composition->saveAsTemplate($pageUniqueId, $templateTitle, $templateDescription);

            if (!$result['success']) {
                return new DataResponse([
                    'error' => $result['error'] ?? 'Failed to save template',
                ], Http::STATUS_BAD_REQUEST);
            }

            return new DataResponse($result, Http::STATUS_CREATED);
        } catch (\Exception $e) {
            $this->logger->error('Failed to save as template: ' . $e->getMessage());
            return new DataResponse([
                'error' => $e->getMessage(),
            ], Http::STATUS_INTERNAL_SERVER_ERROR);
        }
    }
    /**
     * Delete a template
     *
     */
    #[NoAdminRequired]
    public function deleteTemplate(string $id): DataResponse {
        try {
            // PageService::deleteTemplate degraded a languageFolder() throw to
            // ['success'=>false, 'error'=>'Templates folder not accessible'] (→ 400
            // below); a throw from the service itself propagated. Reproduce that.
            try {
                $langFolder = $this->folders->languageFolder();
            } catch (\Exception $e) {
                $langFolder = null;
            }

            // Deleting a template is gated like creating one (saveAsTemplate checks
            // canCreateTemplates): without this, any member whose base group permission
            // included delete could remove any template, ignoring a per-user ACL that
            // revoked it. A null langFolder means the templates area is unreachable for
            // this user, which is itself a denial.
            if ($langFolder === null || !$this->templates->canDeleteTemplate($langFolder, $id)) {
                return new DataResponse([
                    'error' => 'You do not have permission to delete this template',
                ], Http::STATUS_FORBIDDEN);
            }

            // $langFolder is non-null past the gate above.
            $result = $this->templates->deleteTemplate($langFolder, $id);

            if (!$result['success']) {
                return new DataResponse([
                    'error' => $result['error'] ?? 'Failed to delete template',
                ], Http::STATUS_BAD_REQUEST);
            }

            return new DataResponse(['success' => true]);
        } catch (\Exception $e) {
            $this->logger->error('Failed to delete template: ' . $e->getMessage());
            return new DataResponse([
                'error' => $e->getMessage(),
            ], Http::STATUS_INTERNAL_SERVER_ERROR);
        }
    }
    /**
     * Create a new page from a template
     *
     */
    #[NoAdminRequired]
    public function createPageFromTemplate(): DataResponse {
        try {
            $templateId = $this->request->getParam('templateId');
            $pageTitle = $this->request->getParam('pageTitle');
            $parentPath = $this->request->getParam('parentPath');

            if (!$templateId || !$pageTitle) {
                return new DataResponse([
                    'error' => 'Missing required parameters: templateId, pageTitle',
                ], Http::STATUS_BAD_REQUEST);
            }

            // createPage is supplied per call as the closure PageService::createPageFromTemplate
            // forwarded: `fn($data,$parent) => $this->createPage(...)`, which itself was a pure
            // delegator to the write service. Bind it straight to the injected PageWriteService —
            // the same container singleton — so the #70 isCreatable preflight runs unchanged.
            $result = $this->composition->createPageFromTemplate(
                $templateId,
                $pageTitle,
                $parentPath,
                fn(array $data, ?string $parentPath = null): array => $this->pageWrite->createPage($data, $parentPath)
            );

            if (!$result['success']) {
                return new DataResponse([
                    'error' => $result['error'] ?? 'Failed to create page from template',
                ], Http::STATUS_BAD_REQUEST);
            }

            return new DataResponse($result, Http::STATUS_CREATED);
        } catch (\Exception $e) {
            $this->logger->error('Failed to create page from template: ' . $e->getMessage());
            return new DataResponse([
                'error' => $e->getMessage(),
            ], Http::STATUS_INTERNAL_SERVER_ERROR);
        }
    }
}
