<?php
declare(strict_types=1);

namespace OCA\IntraVox\Tests\Unit\Service;

use OCA\IntraVox\Service\GroupFolders\AclRuleFolder;
use PHPUnit\Framework\TestCase;

/**
 * How conflicting ACL rules combine (issue #116).
 *
 * The reported symptom is a user in two groups with opposing rights who ends up
 * with only one group's answer. 3.1.0 fixed that for two rules on the SAME path
 * by merging them before applying. It did not fix the shape administrators
 * actually produce — rights set at different depths of one path — because the
 * per-path results were still folded onto each other one at a time, so a deny
 * deeper down erased another group's allow higher up.
 *
 * These cases pin AclRuleFolder against groupfolders'
 * ACLManager::calculatePermissionsForPath(), whose arithmetic it reproduces:
 * Rule::mergeRules() (OR the masks, OR the permissions) and
 * Rule::applyPermissions() (masked bits overwrite, unmasked bits inherit).
 *
 * Bits: 1 read, 2 update, 4 create, 8 delete, 16 share; 31 = all.
 */
class PermissionAclInheritanceTest extends TestCase {

    private const READ = 1;
    private const WRITE = 2;
    private const ALL = 31;

    private function fold(array $rulesByPath, array $orderedPaths, int $base, bool $perUser): int {
        return (new AclRuleFolder())->fold($rulesByPath, $orderedPaths, $base, $perUser);
    }

    /** An allow rule: the bits in $bits are granted. */
    private function allow(int $bits): array {
        return ['mask' => $bits, 'permissions' => $bits];
    }

    /** A deny rule: the bits in $bits are masked and withheld. */
    private function deny(int $bits): array {
        return ['mask' => $bits, 'permissions' => 0];
    }

    public function testNoRulesLeavesTheFolderGrantUntouched(): void {
        $this->assertSame(self::ALL, $this->fold([], ['files'], self::ALL, false));
    }

    /**
     * The 3.1.0 case, kept as a regression: two rules on ONE path merge with
     * allow overwriting deny, whichever order the database returns them in.
     */
    public function testConflictOnOnePathLetsAllowWin(): void {
        $rules = ['files/en/dept-a' => [
            'group/G-A' => [$this->allow(self::WRITE)],
            'group/G-B' => [$this->deny(self::WRITE)],
        ]];
        $paths = ['files', 'files/en', 'files/en/dept-a'];

        foreach ([false, true] as $perUser) {
            $this->assertSame(
                self::ALL,
                $this->fold($rules, $paths, self::ALL, $perUser),
                'allow must overwrite deny within one path, in both modes'
            );
        }
    }

    /**
     * THE REPORTED BUG. Group A allows write on the department folder, Group B
     * denies it one level up. Before this fix the deny and the allow were folded
     * onto one accumulator in path order, so whichever sat deeper won and one of
     * the user's two groups was, in the reporter's words, simply ignored.
     *
     * With acl-inherit-per-user on, each group's own chain is resolved first —
     * Group A ends at allow, Group B at deny — and merging those keeps the allow.
     */
    public function testAllowDeeperThanDenySurvivesInPerUserMode(): void {
        $rules = [
            'files/en' => ['group/G-B' => [$this->deny(self::WRITE)]],
            'files/en/dept-a' => ['group/G-A' => [$this->allow(self::WRITE)]],
        ];
        $paths = ['files', 'files/en', 'files/en/dept-a'];

        $this->assertSame(
            self::ALL,
            $this->fold($rules, $paths, self::ALL, true),
            "the allowing group's grant must survive a deny from another group"
        );
    }

    /**
     * The same shape with the depths swapped: Group A allows high, Group B denies
     * low. This is the case that stayed broken after 3.1.0 — in path mode the
     * deeper deny still wins, which is groupfolders' documented default and what
     * Files shows, so IntraVox must agree rather than be generous.
     */
    public function testDenyDeeperThanAllowIsKeptInPathModeAndLiftedPerUser(): void {
        $rules = [
            'files/en' => ['group/G-A' => [$this->allow(self::WRITE)]],
            'files/en/dept-a' => ['group/G-B' => [$this->deny(self::WRITE)]],
        ];
        $paths = ['files', 'files/en', 'files/en/dept-a'];

        $this->assertSame(
            self::ALL & ~self::WRITE,
            $this->fold($rules, $paths, self::ALL, false),
            'path mode: the deeper rule overwrites, matching Files'
        );

        $this->assertSame(
            self::ALL,
            $this->fold($rules, $paths, self::ALL, true),
            'per-user mode: each mapping is resolved on its own first'
        );
    }

    /**
     * A mapping's OWN deeper rule still overrides its own shallower one, even in
     * per-user mode. Without this the mode would stop being an inheritance model
     * and just OR everything the user has ever been granted.
     */
    public function testAMappingsOwnDeeperRuleStillOverridesItself(): void {
        $rules = [
            'files/en' => ['group/G-A' => [$this->allow(self::WRITE)]],
            'files/en/dept-a' => ['group/G-A' => [$this->deny(self::WRITE)]],
        ];
        $paths = ['files', 'files/en', 'files/en/dept-a'];

        $this->assertSame(
            self::ALL & ~self::WRITE,
            $this->fold($rules, $paths, self::ALL, true),
            'within one mapping the deeper rule wins'
        );
    }

    /**
     * A 'user' rule is one mapping among the others, not a trump card.
     *
     * IntraVox used to apply the user rule after the group rules and let it
     * overwrite them. Upstream puts the user mapping in the same flat list as
     * the groups and circles, so within a path allow still beats deny — a
     * per-user deny cannot revoke what a group allows on the same path.
     */
    public function testAUserRuleDoesNotOverrideAGroupAllowOnTheSamePath(): void {
        $rules = ['files/en/dept-a' => [
            'group/G-A' => [$this->allow(self::WRITE)],
            'user/alice' => [$this->deny(self::WRITE)],
        ]];
        $paths = ['files', 'files/en', 'files/en/dept-a'];

        foreach ([false, true] as $perUser) {
            $this->assertSame(
                self::ALL,
                $this->fold($rules, $paths, self::ALL, $perUser),
                'a user rule merges with the group rules, it does not trump them'
            );
        }
    }

    /**
     * A circle (Team) grant is a mapping like any other, and a circle id is a
     * different id space from a group id — so 'circle/X' and 'group/X' are two
     * accumulators, not one.
     */
    public function testCircleAndGroupWithTheSameIdAreSeparateMappings(): void {
        $rules = [
            'files/en' => ['circle/shared-id' => [$this->deny(self::WRITE)]],
            'files/en/dept-a' => ['group/shared-id' => [$this->allow(self::WRITE)]],
        ];
        $paths = ['files', 'files/en', 'files/en/dept-a'];

        $this->assertSame(
            self::ALL,
            $this->fold($rules, $paths, self::ALL, true),
            'the group chain must not be merged into the circle chain'
        );
    }

    /**
     * A federated account's uid carries the remote ('alice@cloud.example'), and
     * it arrives as a 'user' mapping like any local one. Nothing about the fold
     * treats it specially; this pins that the key survives the '@' intact and
     * still merges rather than overriding.
     */
    public function testFederatedUserMappingBehavesLikeAnyOtherMapping(): void {
        $rules = [
            'files/en' => ['group/G-A' => [$this->deny(self::WRITE)]],
            'files/en/dept-a' => ['user/alice@cloud.example' => [$this->allow(self::WRITE)]],
        ];
        $paths = ['files', 'files/en', 'files/en/dept-a'];

        $this->assertSame(
            self::ALL,
            $this->fold($rules, $paths, self::ALL, true),
            'a federated uid is just another mapping id'
        );
    }

    /**
     * An allow rule can raise a bit within the folder, but never past what the
     * team folder grants the user. Upstream gets this from the mount
     * (`$cacheEntry['permissions'] &= $aclRootPermissions`); here it is the AND
     * that closes both branches of the fold.
     */
    public function testAnAllowRuleCannotExceedTheFolderGrant(): void {
        $rules = ['files/en/dept-a' => ['group/G-A' => [$this->allow(self::ALL)]]];
        $paths = ['files', 'files/en', 'files/en/dept-a'];

        foreach ([false, true] as $perUser) {
            $this->assertSame(
                self::READ,
                $this->fold($rules, $paths, self::READ, $perUser),
                'a rule cannot grant what the team folder withholds'
            );
        }
    }

    /**
     * Unmasked bits are inherited untouched: a rule about write must not disturb
     * read, delete or share.
     */
    public function testUnmaskedBitsAreInherited(): void {
        $rules = ['files/en/dept-a' => ['group/G-A' => [$this->deny(self::WRITE)]]];
        $paths = ['files', 'files/en', 'files/en/dept-a'];

        foreach ([false, true] as $perUser) {
            $this->assertSame(
                self::ALL & ~self::WRITE,
                $this->fold($rules, $paths, self::ALL, $perUser),
                'only the masked bit changes'
            );
        }
    }
}
