<?php
/**
 * Test script to check MetaVox export
 * Run from command line: php test-metavox-export.php
 */

require_once __DIR__ . '/vendor/autoload.php';

// Bootstrap Nextcloud
$_SERVER['REQUEST_URI'] = '/';
$_SERVER['SCRIPT_NAME'] = '/index.php';
$_SERVER['SERVER_NAME'] = 'localhost';

try {
    require_once __DIR__ . '/../../lib/base.php';

    echo "=== MetaVox Export Test ===\n\n";

    // Get services
    $appManager = \OC::$server->get(\OCP\App\IAppManager::class);
    $logger = \OC::$server->get(\Psr\Log\LoggerInterface::class);

    // Check if MetaVox is installed
    echo "1. Checking MetaVox installation...\n";
    $metaVoxInstalled = $appManager->isInstalled('metavox');
    $metaVoxEnabled = $appManager->isEnabledForUser('metavox');

    echo "   - Installed: " . ($metaVoxInstalled ? "YES" : "NO") . "\n";
    echo "   - Enabled: " . ($metaVoxEnabled ? "YES" : "NO") . "\n";

    if ($metaVoxInstalled && $metaVoxEnabled) {
        $version = $appManager->getAppVersion('metavox');
        echo "   - Version: $version\n";

        // Try to get FieldService
        echo "\n2. Checking FieldService availability...\n";
        try {
            $fieldService = \OC::$server->get(\OCA\MetaVox\Service\FieldService::class);
            echo "   - FieldService: AVAILABLE\n";

            // Try to get all fields
            echo "\n3. Retrieving field definitions...\n";
            $fields = $fieldService->getAllFields();
            echo "   - Field count: " . count($fields) . "\n";

            if (count($fields) > 0) {
                echo "   - Fields:\n";
                foreach ($fields as $field) {
                    echo "     * " . $field['field_name'] . " (" . $field['field_type'] . ")\n";
                }
            }

            // Try to get metadata for a file
            echo "\n4. Testing bulk metadata retrieval...\n";

            // Get IntraVox groupfolder
            $setupService = \OC::$server->get(\OCA\IntraVox\Service\SetupService::class);
            $groupfolderId = $setupService->getGroupFolderId();
            echo "   - Groupfolder ID: $groupfolderId\n";

            // Get a sample file ID from IntraVox
            $folder = $setupService->getIntraVoxFolder();
            $nlFolder = $folder->get('nl');

            if ($nlFolder->nodeExists('home.json')) {
                $homeFile = $nlFolder->get('home.json');
                $fileId = $homeFile->getId();

                echo "   - Sample file ID (home.json): $fileId\n";

                // Try to get metadata
                $metadata = $fieldService->getBulkFileMetadata([$fileId]);
                echo "   - Metadata retrieved: " . (count($metadata) > 0 ? "YES" : "NO") . "\n";

                if (isset($metadata[$fileId])) {
                    echo "   - Fields for file $fileId: " . count($metadata[$fileId]) . "\n";
                    foreach ($metadata[$fileId] as $field) {
                        $value = $field['value'] ?? '(empty)';
                        echo "     * " . $field['field_name'] . " = " . $value . "\n";
                    }
                }
            }

        } catch (\Exception $e) {
            echo "   - ERROR: " . $e->getMessage() . "\n";
        }
    } else {
        echo "\n   MetaVox is not available. Export will not include metadata.\n";
    }

    echo "\n=== Test Complete ===\n";

} catch (\Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}
