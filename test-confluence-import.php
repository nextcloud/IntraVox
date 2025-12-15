#!/usr/bin/env php
<?php
/**
 * Test script for Confluence importer
 * Tests the parser with local Confluence Storage Format HTML
 */

// Load Nextcloud autoloader
require_once __DIR__ . '/../../lib/base.php';

use OCA\IntraVox\Service\Import\ConfluenceImporter;
use Psr\Log\LoggerInterface;

// Simple logger for testing
class ConsoleLogger implements LoggerInterface {
    public function emergency($message, array $context = []): void { $this->log('EMERGENCY', $message, $context); }
    public function alert($message, array $context = []): void { $this->log('ALERT', $message, $context); }
    public function critical($message, array $context = []): void { $this->log('CRITICAL', $message, $context); }
    public function error($message, array $context = []): void { $this->log('ERROR', $message, $context); }
    public function warning($message, array $context = []): void { $this->log('WARNING', $message, $context); }
    public function notice($message, array $context = []): void { $this->log('NOTICE', $message, $context); }
    public function info($message, array $context = []): void { $this->log('INFO', $message, $context); }
    public function debug($message, array $context = []): void { $this->log('DEBUG', $message, $context); }

    public function log($level, $message, array $context = []): void {
        echo "[$level] $message\n";
        if (!empty($context)) {
            echo "  Context: " . json_encode($context, JSON_PRETTY_PRINT) . "\n";
        }
    }
}

// Load test file
$testFile = __DIR__ . '/test-confluence-storage.html';
if (!file_exists($testFile)) {
    die("❌ Test file not found: $testFile\n");
}

echo "🧪 Confluence Import Test\n";
echo "========================\n\n";

$html = file_get_contents($testFile);
echo "📄 Loaded test file: " . strlen($html) . " bytes\n\n";

// Create importer
$logger = new ConsoleLogger();
$importer = new ConfluenceImporter($logger);

// Prepare page data (simulating what comes from API)
$pageData = [
    'title' => 'Test Documentation',
    'id' => 'test-123',
    'body' => $html,
    'spaceKey' => 'TEST',
];

echo "🔄 Parsing Confluence content...\n\n";

try {
    // Parse the content
    $result = $importer->parse($pageData);

    echo "✅ Parsing completed successfully!\n\n";

    echo "📊 Results:\n";
    echo "  Pages: " . count($result->pages) . "\n";
    echo "  Language: " . $result->language . "\n";
    echo "  Media downloads: " . count($result->mediaDownloads) . "\n\n";

    if (!empty($result->pages)) {
        $page = $result->pages[0];
        echo "📄 First Page:\n";
        echo "  Title: " . $page->title . "\n";
        echo "  Slug: " . $page->slug . "\n";
        echo "  Content blocks: " . count($page->contentBlocks) . "\n\n";

        echo "📦 Content Blocks:\n";
        foreach ($page->contentBlocks as $i => $block) {
            $blockNum = $i + 1;
            echo "  $blockNum. " . $block->getType() . "\n";

            // Show details for different block types
            $blockArray = $block->toArray();
            switch ($block->getType()) {
                case 'heading':
                    echo "     Level: " . $blockArray['level'] . "\n";
                    echo "     Text: " . substr($blockArray['text'], 0, 50) . "...\n";
                    break;
                case 'panel':
                    echo "     Panel Type: " . $blockArray['panelType'] . "\n";
                    if (!empty($blockArray['title'])) {
                        echo "     Title: " . $blockArray['title'] . "\n";
                    }
                    echo "     Content: " . substr($blockArray['content'], 0, 50) . "...\n";
                    break;
                case 'code':
                    echo "     Language: " . ($blockArray['language'] ?? 'none') . "\n";
                    echo "     Line numbers: " . ($blockArray['lineNumbers'] ? 'yes' : 'no') . "\n";
                    echo "     Code length: " . strlen($blockArray['code']) . " chars\n";
                    break;
                case 'html':
                    $preview = substr(strip_tags($blockArray['html']), 0, 60);
                    echo "     Content: " . $preview . "...\n";
                    break;
            }
        }

        echo "\n";

        // Convert to IntraVox export format
        echo "🔄 Converting to IntraVox format...\n";
        $export = $importer->convertToIntraVoxExport($result);

        echo "✅ Conversion completed!\n\n";
        echo "📦 Export structure:\n";
        echo "  Language: " . $export['language'] . "\n";
        echo "  Pages: " . count($export['pages']) . "\n";

        if (!empty($export['pages'])) {
            $exportPage = $export['pages'][0];
            echo "\n📄 First exported page:\n";
            echo "  UUID: " . $exportPage['uuid'] . "\n";
            echo "  Title: " . $exportPage['title'] . "\n";
            echo "  Widgets: " . count($exportPage['content']) . "\n\n";

            echo "🧩 Widgets:\n";
            foreach (array_slice($exportPage['content'], 0, 10) as $i => $widget) {
                $widgetNum = $i + 1;
                echo "  $widgetNum. " . $widget['type'] . "\n";

                // Show widget details
                if ($widget['type'] === 'text') {
                    $preview = substr(strip_tags($widget['props']['content'] ?? ''), 0, 50);
                    echo "     Content: $preview...\n";
                }
            }

            if (count($exportPage['content']) > 10) {
                $remaining = count($exportPage['content']) - 10;
                echo "  ... and $remaining more widgets\n";
            }
        }

        // Save to JSON file for inspection
        $outputFile = __DIR__ . '/test-output.json';
        file_put_contents($outputFile, json_encode($export, JSON_PRETTY_PRINT));
        echo "\n💾 Full export saved to: $outputFile\n";

    } else {
        echo "⚠️  No pages were parsed\n";
    }

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "   File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "   Trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}

echo "\n✅ Test completed successfully!\n";
