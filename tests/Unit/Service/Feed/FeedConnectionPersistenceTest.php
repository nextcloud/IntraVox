<?php
declare(strict_types=1);

namespace OCA\IntraVox\Tests\Unit\Service\Feed;

use OCA\IntraVox\Service\FeedReaderService;
use OCP\IConfig;
use OCP\Security\ICrypto;
use PHPUnit\Framework\TestCase;

/**
 * What survives a round trip through saveConnections().
 *
 * Every other mapping value is a JSON path and goes through sanitizeJsonPath(),
 * which allows [a-zA-Z0-9_.] and drops the rest. The URL template is the one
 * that must keep its punctuation — a pattern without slashes and braces is not a
 * pattern — so it takes a different route, and that difference is worth pinning.
 */
class FeedConnectionPersistenceTest extends TestCase {
    /** @var array<string,mixed> the connection as it was written to appconfig */
    private array $saved = [];

    private function service(string $stored = '[]'): FeedReaderService {
        $service = (new \ReflectionClass(FeedReaderService::class))->newInstanceWithoutConstructor();

        $config = $this->createMock(IConfig::class);
        $config->method('getAppValue')->willReturn($stored);
        $config->method('setAppValue')->willReturnCallback(
            function (string $app, string $key, string $value): void {
                $this->saved = json_decode($value, true)[0] ?? [];
            }
        );
        (new \ReflectionProperty(FeedReaderService::class, 'config'))->setValue($service, $config);

        $crypto = $this->createMock(ICrypto::class);
        $crypto->method('encrypt')->willReturnCallback(fn (string $v): string => 'enc:' . $v);
        (new \ReflectionProperty(FeedReaderService::class, 'crypto'))->setValue($service, $crypto);

        return $service;
    }

    public function testTheTemplateKeepsThePunctuationAPatternNeeds(): void {
        $this->service()->saveConnections([[
            'name' => 'Talk',
            'type' => 'custom',
            'responseMapping' => [
                'title' => 'message',
                'urlTemplate' => '/call/{token}#message_{id}',
            ],
        ]]);

        $this->assertSame('/call/{token}#message_{id}', $this->saved['responseMapping']['urlTemplate']);
        // The neighbouring path field is sanitised, as before.
        $this->assertSame('message', $this->saved['responseMapping']['title']);
    }

    /** A connection stored before the field existed still loads and saves. */
    public function testAMappingWithoutATemplateRoundTripsToAnEmptyOne(): void {
        $this->service()->saveConnections([[
            'name' => 'Jira',
            'type' => 'jira',
            'responseMapping' => ['items' => 'issues', 'title' => 'fields.summary'],
        ]]);

        $this->assertSame('', $this->saved['responseMapping']['urlTemplate']);
    }

    /** LMS types have no REST mapping at all, and gain none. */
    public function testAnLmsConnectionStoresNoMapping(): void {
        $this->service()->saveConnections([[
            'name' => 'Moodle',
            'type' => 'moodle',
            'responseMapping' => ['urlTemplate' => '/should/not/{be}/stored'],
        ]]);

        $this->assertArrayNotHasKey('responseMapping', $this->saved);
    }
}
