<?php
declare(strict_types=1);

namespace OCA\IntraVox\Service\Template;

use OCP\Files\File;
use OCP\Files\Folder;
use OCP\Files\NotFoundException;
use Psr\Log\LoggerInterface;

/**
 * Storage and lookup for page templates in a language's `_templates`
 * folder, extracted from PageService (service split, PR-14).
 *
 * The caller supplies the LANGUAGE FOLDER — this service never resolves
 * one itself, so the locator machinery stays in one place while the split
 * is under way. What stays behind in PageService is the orchestration
 * that genuinely spans domains: saving a page AS a template and creating
 * a page FROM one (both compose getPage/createPage with media copying).
 *
 * A template is `{_templates}/{id}/{id}.json` plus an optional `_media`
 * folder — the page model, one level down.
 */
class PageTemplateService {
    private TemplateMetadataExtractor $metadata;
    private LoggerInterface $logger;

    public function __construct(
        TemplateMetadataExtractor $metadata,
        LoggerInterface $logger
    ) {
        $this->metadata = $metadata;
        $this->logger = $logger;
    }

    /**
     * The language's `_templates` folder, or null when it does not exist
     * or cannot be read.
     */
    public function templatesFolder(Folder $languageFolder): ?Folder {
        try {
            if ($languageFolder->nodeExists('_templates')) {
                $templatesFolder = $languageFolder->get('_templates');
                if ($templatesFolder instanceof Folder) {
                    return $templatesFolder;
                }
            }
            return null;
        } catch (\Exception $e) {
            $this->logger->warning('Could not access templates folder: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * All templates in the language, with preview metadata, sorted by title.
     */
    public function listTemplates(Folder $languageFolder): array {
        $templatesFolder = $this->templatesFolder($languageFolder);
        if ($templatesFolder === null) {
            return [];
        }

        $templates = [];

        try {
            foreach ($templatesFolder->getDirectoryListing() as $item) {
                if (!($item instanceof Folder)) {
                    continue;
                }

                $templateId = $item->getName();

                // Skip special folders
                if (str_starts_with($templateId, '.') || $templateId === '_media') {
                    continue;
                }

                try {
                    $jsonFile = $item->get($templateId . '.json');
                    if (!($jsonFile instanceof File)) {
                        continue;
                    }

                    $content = json_decode($jsonFile->getContent(), true);
                    if (!$content) {
                        continue;
                    }

                    $templates[] = [
                        'id' => $templateId,
                        'uniqueId' => $content['uniqueId'] ?? 'template-' . $templateId,
                        'title' => $content['title'] ?? $templateId,
                        'description' => $content['description'] ?? '',
                        'created' => $content['created'] ?? $jsonFile->getMTime(),
                        'modified' => $jsonFile->getMTime(),
                        'createdBy' => $content['createdBy'] ?? '',
                        'preview' => $this->metadata->extract($content),
                    ];
                } catch (NotFoundException $e) {
                    // Template folder exists but no JSON file, skip
                    continue;
                }
            }
        } catch (\Exception $e) {
            $this->logger->error('Failed to list templates: ' . $e->getMessage());
        }

        usort($templates, fn($a, $b) => strcasecmp($a['title'], $b['title']));

        return $templates;
    }

    /**
     * Full content of one template, or null when absent/invalid.
     */
    /**
     * A template id is a plain folder-name slug. Rejecting anything else stops
     * it from escaping the _templates folder: the raw id reaches Folder::get()
     * and Folder::delete(), so '.' (URL-encoded %2e) resolved to the _templates
     * folder itself and a DELETE wiped every template in the language.
     * '' / '.' / '..' / any '/' are refused.
     */
    private function isValidTemplateId(string $templateId): bool {
        return $templateId !== '' && (bool)preg_match('/^[a-zA-Z0-9_-]+$/', $templateId);
    }

    public function getTemplate(Folder $languageFolder, string $templateId): ?array {
        if (!$this->isValidTemplateId($templateId)) {
            return null;
        }
        $templatesFolder = $this->templatesFolder($languageFolder);
        if ($templatesFolder === null) {
            return null;
        }

        try {
            if (!$templatesFolder->nodeExists($templateId)) {
                return null;
            }

            $templateFolder = $templatesFolder->get($templateId);
            if (!($templateFolder instanceof Folder)) {
                return null;
            }

            $jsonFile = $templateFolder->get($templateId . '.json');
            if (!($jsonFile instanceof File)) {
                return null;
            }

            $content = json_decode($jsonFile->getContent(), true);
            if (!$content) {
                return null;
            }

            return $content;
        } catch (NotFoundException $e) {
            return null;
        } catch (\Exception $e) {
            $this->logger->error('Failed to get template: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Delete a template folder (JSON + media). Result-array shape is part
     * of the API contract.
     */
    public function deleteTemplate(Folder $languageFolder, string $templateId): array {
        try {
            if (!$this->isValidTemplateId($templateId)) {
                return ['success' => false, 'error' => 'Template not found'];
            }
            $templatesFolder = $this->templatesFolder($languageFolder);
            if ($templatesFolder === null) {
                return ['success' => false, 'error' => 'Templates folder not accessible'];
            }

            if (!$templatesFolder->nodeExists($templateId)) {
                return ['success' => false, 'error' => 'Template not found'];
            }

            $templateFolder = $templatesFolder->get($templateId);
            $templateFolder->delete();

            $this->logger->info('Deleted template: ' . $templateId);

            return ['success' => true];
        } catch (\Exception $e) {
            $this->logger->error('Failed to delete template: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Whether templates can be created in this language: create permission
     * on `_templates` when it exists, on the language folder otherwise
     * (creating the first template creates the folder).
     */
    public function canCreateTemplates(Folder $languageFolder): bool {
        try {
            if (!$languageFolder->nodeExists('_templates')) {
                return $languageFolder->isCreatable();
            }

            $templatesFolder = $languageFolder->get('_templates');
            return $templatesFolder->isCreatable();
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Whether this user may delete the given template: the delete permission on
     * the template's own folder, in the user's ACL-filtered view.
     *
     * The controller gates saveAsTemplate on canCreateTemplates() but deleteTemplate
     * gated on nothing at the app layer, so any member whose base group permission
     * included delete (typically 7) could remove any template regardless of a per-user
     * ACL rule that revoked it. This is the delete-side companion: it answers against
     * the exact folder that delete() would remove, so an ACL revoking delete on
     * `_templates` (or on the specific template) is honoured. A missing/invalid
     * template or an unreadable `_templates` reads as "no" — never as "unset, so allow".
     */
    public function canDeleteTemplate(Folder $languageFolder, string $templateId): bool {
        try {
            if (!$this->isValidTemplateId($templateId)) {
                return false;
            }

            $templatesFolder = $this->templatesFolder($languageFolder);
            if ($templatesFolder === null || !$templatesFolder->nodeExists($templateId)) {
                return false;
            }

            return $templatesFolder->get($templateId)->isDeletable();
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Reserve a collision-free template folder — creating `_templates` when
     * missing — plus its `_media` subfolder.
     *
     * @return array{0:string, 1:Folder, 2:Folder} [finalId, templateFolder, mediaFolder]
     */
    public function newTemplateFolder(Folder $languageFolder, string $desiredId): array {
        if (!$this->isValidTemplateId($desiredId)) {
            throw new \InvalidArgumentException('Invalid template id');
        }
        if (!$languageFolder->nodeExists('_templates')) {
            $languageFolder->newFolder('_templates');
        }
        $templatesFolder = $languageFolder->get('_templates');

        // Handle duplicate names by appending a number
        $templateId = $desiredId;
        $counter = 1;
        while ($templatesFolder->nodeExists($templateId)) {
            $counter++;
            $templateId = $desiredId . '-' . $counter;
        }

        $templateFolder = $templatesFolder->newFolder($templateId);
        $mediaFolder = $templateFolder->newFolder('_media');

        return [$templateId, $templateFolder, $mediaFolder];
    }
}
