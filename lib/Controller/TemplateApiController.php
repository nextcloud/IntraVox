<?php
declare(strict_types=1);

namespace OCA\IntraVox\Controller;

use OCA\IntraVox\Service\PageService;
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
 * dozen unrelated resources. Templates is the most self-contained of them: one
 * collaborator (PageService, which delegates to PageTemplateService) and no
 * shared helpers.
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
        private PageService $pageService,
        private LoggerInterface $logger,
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
            $templates = $this->pageService->listTemplates();
            $canCreate = $this->pageService->canCreateTemplates();

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
            $template = $this->pageService->getTemplate($id);

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

            // Check if user can create templates
            if (!$this->pageService->canCreateTemplates()) {
                return new DataResponse([
                    'error' => 'You do not have permission to create templates',
                ], Http::STATUS_FORBIDDEN);
            }

            $result = $this->pageService->saveAsTemplate($pageUniqueId, $templateTitle, $templateDescription);

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
            $result = $this->pageService->deleteTemplate($id);

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

            $result = $this->pageService->createPageFromTemplate($templateId, $pageTitle, $parentPath);

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
