<?php
declare(strict_types=1);

namespace OCA\IntraVox\Service\GroupFolders;

/**
 * How conflicting ACL rules combine into one permission bitmask.
 *
 * This is groupfolders' ACLManager::calculatePermissionsForPath() arithmetic,
 * reproduced because IntraVox reads the rules straight from group_folders_acl
 * rather than going through the ACL engine (which is scoped to a mounted node,
 * and IntraVox resolves permissions for paths it has not mounted). It lives in
 * its own class because it is a pure function of its inputs: no database, no
 * session, no groupfolders classes — which is what makes the whole matrix of
 * issue #116 testable without a Nextcloud instance.
 *
 * IntraVox previously merged rules only WITHIN one path segment and then folded
 * the per-path results onto each other in path order. That is one of the two
 * modes upstream has, and it was applied unconditionally — so on an instance
 * configured for the other mode, a deny on a space erased an allow that another
 * of the user's groups had on a department inside it, and the user was told they
 * had no rights they plainly had in Files (issue #116).
 *
 * Bit semantics, per bit of a rule:
 *   mask 0            — inherit: the incoming bit passes through untouched.
 *   mask 1, perm 1    — allow: the bit is forced on.
 *   mask 1, perm 0    — deny: the bit is cleared.
 */
class AclRuleFolder {

	/**
	 * Combine the collected rules into the effective permission bitmask.
	 *
	 * PER-MAPPING MODE ($inheritMergePerUser; app config groupfolders /
	 * acl-inherit-per-user). Each mapping's own rules are folded down the path
	 * first — deepest wins WITHIN that mapping — and only then are the results
	 * merged with allow overwriting deny. A user allowed through Group A keeps
	 * that right even when Group B is denied deeper down.
	 *
	 * PATH MODE (upstream's default). Rules on one path are merged, then applied
	 * one path at a time, parent first, so a deeper deny does overwrite a
	 * shallower allow across mappings. That is not a bug to be corrected here:
	 * it is what Files shows, and an intranet that silently disagreed with the
	 * file list beside it would be the worse outcome.
	 *
	 * Both modes end by intersecting with the folder-level grant. Upstream seeds
	 * the fold with getBasePermission() — the folder's ACL default rather than
	 * the user's grant — and the mount then ANDs the result with that grant
	 * (MountProvider: `$cacheEntry['permissions'] &= $aclRootPermissions`). So an
	 * allow rule may raise a bit within the folder's default, but never beyond
	 * what the team folder gives this user; the AND here preserves that.
	 *
	 * @param array<string, array<string, list<array{mask:int,permissions:int}>>> $rulesByPath
	 *        path => "type/id" => the rules that mapping has on that path
	 * @param list<string> $orderedPaths every checked path, parent first
	 * @param int $basePermissions the folder-level grant for this user
	 */
	public function fold(array $rulesByPath, array $orderedPaths, int $basePermissions, bool $inheritMergePerUser): int {
		if ($rulesByPath === []) {
			return $basePermissions;
		}

		$effective = $inheritMergePerUser
			? $this->foldPerMapping($rulesByPath, $orderedPaths, $basePermissions)
			: $this->foldPerPath($rulesByPath, $orderedPaths, $basePermissions);

		return $effective & $basePermissions;
	}

	/**
	 * Resolve each mapping's chain on its own, then merge the results.
	 *
	 * @param array<string, array<string, list<array{mask:int,permissions:int}>>> $rulesByPath
	 * @param list<string> $orderedPaths
	 */
	private function foldPerMapping(array $rulesByPath, array $orderedPaths, int $basePermissions): int {
		$perMapping = [];

		foreach ($orderedPaths as $path) {
			foreach ($rulesByPath[$path] ?? [] as $key => $rules) {
				$merged = $this->merge($rules);
				$current = $perMapping[$key] ?? ['mask' => 0, 'permissions' => 0];

				// Rule::applyRule(): the deeper rule is applied on top of what
				// this mapping had so far, and the masks accumulate.
				$perMapping[$key] = [
					'mask' => $current['mask'] | $merged['mask'],
					'permissions' => $this->apply($current['permissions'], $merged),
				];
			}
		}

		return $this->apply($basePermissions, $this->merge(array_values($perMapping)));
	}

	/**
	 * Merge per path, then apply parent first so deeper rules overwrite.
	 *
	 * @param array<string, array<string, list<array{mask:int,permissions:int}>>> $rulesByPath
	 * @param list<string> $orderedPaths
	 */
	private function foldPerPath(array $rulesByPath, array $orderedPaths, int $basePermissions): int {
		$effective = $basePermissions;

		foreach ($orderedPaths as $path) {
			if (!isset($rulesByPath[$path])) {
				continue;
			}

			$flat = [];
			foreach ($rulesByPath[$path] as $rules) {
				foreach ($rules as $rule) {
					$flat[] = $rule;
				}
			}

			$effective = $this->apply($effective, $this->merge($flat));
		}

		return $effective;
	}

	/**
	 * groupfolders' Rule::mergeRules(): OR the masks, OR the permissions.
	 *
	 * The plain OR on the permissions is what makes allow overwrite deny inside
	 * one merge pool, and it is only safe because each rule's permissions were
	 * masked when collected (upstream does this in Rule::__construct). An
	 * inherit bit must never be able to contribute a phantom allow.
	 *
	 * @param list<array{mask:int,permissions:int}> $rules
	 * @return array{mask:int,permissions:int}
	 */
	private function merge(array $rules): array {
		$mask = 0;
		$permissions = 0;

		foreach ($rules as $rule) {
			$mask |= $rule['mask'];
			$permissions |= $rule['permissions'];
		}

		return ['mask' => $mask, 'permissions' => $permissions];
	}

	/**
	 * groupfolders' Rule::applyPermissions(): the rule's masked bits overwrite
	 * the incoming ones, unmasked bits are inherited untouched.
	 *
	 * @param array{mask:int,permissions:int} $rule
	 */
	private function apply(int $permissions, array $rule): int {
		return ($permissions & (~$rule['mask'] | $rule['permissions']))
			| ($rule['mask'] & $rule['permissions']);
	}
}
