<?php
declare(strict_types=1);

namespace OCA\IntraVox\Tests\Unit\Service;

use OCA\IntraVox\Service\GroupFolders\GroupFoldersGateway;
use OCA\IntraVox\Service\PermissionService;
use PHPUnit\Framework\TestCase;

/**
 * The tree/news caches are keyed on the user's GROUP set, which assumes that
 * everyone in the same groups sees the same content. Advanced Permissions
 * break that assumption: two users in one group can hold different rights per
 * path, and the group-keyed entry then serves one user's filtered view to the
 * other.
 *
 * That is issue #112 -- a page stayed invisible for a user who was allowed to
 * read it, purely because a colleague without that right populated the cache
 * first. getCacheDiscriminator() closes it by narrowing the key to the user
 * exactly when ACLs are on.
 *
 * Both halves are locked down here, because "fix it by always keying per user"
 * is the obvious wrong move: it would multiply the keyspace by the number of
 * users on every installation, including the majority that runs without ACLs
 * and shares correctly today.
 */
class PermissionCacheDiscriminatorTest extends TestCase {
    private function service(bool $aclEnabled, ?string $userId = 'alice'): PermissionService {
        $service = (new \ReflectionClass(PermissionService::class))->newInstanceWithoutConstructor();

        $gateway = $this->createMock(GroupFoldersGateway::class);
        $gateway->method('hasAcl')->willReturn($aclEnabled);

        $this->set($service, 'groupFolders', $gateway);
        $this->set($service, 'userId', $userId);
        $this->set($service, 'logger', new \Psr\Log\NullLogger());
        // Skip the groupfolder lookup: resolution is not what this tests.
        $this->set($service, 'groupFolderIdCache', ['IntraVox' => 1]);

        return $service;
    }

    private function set(object $object, string $property, mixed $value): void {
        // No setAccessible(): a no-op since PHP 8.1 and deprecated in 8.5.
        (new \ReflectionProperty(PermissionService::class, $property))->setValue($object, $value);
    }

    public function testWithoutAclsTheCacheStaysSharedPerGroup(): void {
        $this->assertSame(
            '',
            $this->service(false)->getCacheDiscriminator(),
            'no ACLs means group members really do see the same tree; keying per user would '
            . 'multiply the keyspace for no gain'
        );
    }

    public function testWithAclsTheCacheIsNarrowedToTheUser(): void {
        $discriminator = $this->service(true)->getCacheDiscriminator();

        $this->assertNotSame('', $discriminator, 'ACLs differ within a group, so the key must not be group-wide');
        $this->assertStringStartsWith('_u', $discriminator);
    }

    public function testTwoUsersInTheSameGroupGetDifferentKeysUnderAcls(): void {
        $alice = $this->service(true, 'alice')->getCacheDiscriminator();
        $bob = $this->service(true, 'bob')->getCacheDiscriminator();

        $this->assertNotSame(
            $alice,
            $bob,
            'this is issue #112 itself: same groups, different ACLs, so they must not share a cache entry'
        );
    }

    public function testTheDiscriminatorDoesNotLeakTheRawUserId(): void {
        $discriminator = $this->service(true, 'alice')->getCacheDiscriminator();

        $this->assertStringNotContainsString('alice', $discriminator, 'cache keys travel further than the request');
    }

    public function testAnonymousRequestsKeepTheSharedKey(): void {
        $this->assertSame(
            '',
            $this->service(true, null)->getCacheDiscriminator(),
            'there is no user to narrow to'
        );
    }

    public function testTheAnswerIsMemoisedPerRequest(): void {
        $service = $this->service(true);

        $this->assertSame($service->getCacheDiscriminator(), $service->getCacheDiscriminator());
    }
}
