<?php
declare(strict_types=1);

namespace OCA\IntraVox\Tests\Integration;

use OCA\IntraVox\Service\PublicShareService;
use OCP\Files\Folder;
use OCP\Share\IShare;

/**
 * A share token only opens IntraVox if it points INTO IntraVox. (SHARE-ROOT)
 *
 * PublicShareService::getShareByToken() checked TYPE_LINK and the expiry date
 * and nothing else, so any link share anywhere in the instance opened every
 * anonymous IntraVox endpoint. A user sharing a holiday photo handed out a token
 * that also read IntraVox pages, media, the page tree, and — through
 * CalendarController::getEventsByShare, a #[PublicPage] — the share owner's
 * calendars, with the caller choosing the calendarIds.
 *
 * The gate decides on the STORAGE ID, not the path. A groupfolder resolved
 * through a member's mounted view reports /<user>/files/<mount> — no
 * __groupfolders segment — so a path-prefix test is per-user and wrong. This is
 * the assumption the P0 floor already caught once; the test below pins it.
 */
class ShareRootGateTest extends IntegrationTestCase {

	private function shareService(): PublicShareService {
		return self::server()->get(PublicShareService::class);
	}

	private function shareManager(): \OCP\Share\IManager {
		return self::server()->get(\OCP\Share\IManager::class);
	}

	/** Create a link share on $node, owned by $sharedBy (default: the test user). */
	private function linkShare(\OCP\Files\Node $node, ?string $sharedBy = null): IShare {
		$share = $this->shareManager()->newShare();
		$share->setNode($node);
		$share->setShareType(IShare::TYPE_LINK);
		$share->setSharedBy($sharedBy ?? (string)self::$userId);
		$share->setPermissions(\OCP\Constants::PERMISSION_READ);

		return $this->shareManager()->createShare($share);
	}

	/**
	 * The assumption the whole gate rests on: a mounted groupfolder does NOT
	 * report an internal __groupfolders path, so paths cannot be used, while the
	 * storage id is stable.
	 */
	public function testGroupfolderReportsAMountPathButAStableStorageId(): void {
		$folder = $this->testGroupFolder();

		$this->assertStringNotContainsString(
			'__groupfolders',
			$folder->getPath(),
			'a mounted groupfolder reports a mount path; a path-prefix gate would be wrong'
		);
		$this->assertStringContainsString(
			'__groupfolders',
			$folder->getStorage()->getId(),
			'the storage id is what identifies the groupfolder'
		);
	}

	/**
	 * A share on a file inside the IntraVox folder must be accepted.
	 *
	 * Deliberately NOT the throwaway test groupfolder: the gate resolves the real
	 * IntraVox folder through SetupService, and the test folder is a different
	 * groupfolder on a different storage — which the gate is right to refuse.
	 * Using it here would test the opposite of what this asserts.
	 */
	public function testShareInsideTheFolderIsAccepted(): void {
$this->actingAs('integration-admin', function (): void {
$folder = $this->rootFolder()->getUserFolder('integration-admin')->get('IntraVox');
$this->assertInstanceOf(Folder::class, $folder);

		$file = $folder->newFile('share-gate-inside.txt', 'x');

$share = $this->linkShare($file, 'integration-admin');

		try {
			$resolved = $this->shareService()->resolveIntraVoxLinkShare($share->getToken());

			$this->assertNotNull($resolved, 'a share inside the IntraVox folder must resolve');
			$this->assertSame($share->getId(), $resolved->getId());
		} finally {
			$this->shareManager()->deleteShare($share);
			$file->delete();
		}
});
	}

	/**
	 * The regression: a link share on a file OUTSIDE the groupfolder — an
	 * ordinary file in the user's own home — must not open IntraVox.
	 *
	 * On the pre-fix code this resolved happily, which is the whole finding.
	 */
	public function testShareOutsideTheFolderIsRefused(): void {
		$userFolder = $this->rootFolder()->getUserFolder((string)self::$userId);
		$outsideFile = $userFolder->newFile('share-gate-outside.txt', 'not intravox');

		$share = $this->linkShare($outsideFile);

		try {
			$this->assertNotSame(
				$this->testGroupFolder()->getStorage()->getId(),
				$outsideFile->getStorage()->getId(),
				'precondition: the file must really be on another storage'
			);

			$resolved = $this->shareService()->resolveIntraVoxLinkShare($share->getToken());

			$this->assertNull(
				$resolved,
				'a link share outside the IntraVox folder must not open IntraVox endpoints'
			);
		} finally {
			$this->shareManager()->deleteShare($share);
			$outsideFile->delete();
		}
	}

	/** An unknown token stays a non-answer. */
	public function testUnknownTokenIsRefused(): void {
		$this->assertNull($this->shareService()->resolveIntraVoxLinkShare('definitely-not-a-token'));
	}

	/** validateShareAccess() carries the same gate. */
	public function testValidateShareAccessRefusesAForeignShare(): void {
		$userFolder = $this->rootFolder()->getUserFolder((string)self::$userId);
		$outsideFile = $userFolder->newFile('share-gate-validate.txt', 'nope');

		$share = $this->linkShare($outsideFile);

		try {
			$result = $this->shareService()->validateShareAccess($share->getToken(), 'any-page', 'en');

			// Refusal is what matters. The reason may be not_intravox_share, or
			// page_not_found when the page lookup runs first — either way the
			// foreign share does not grant access.
			$this->assertFalse($result['valid'] ?? true, 'a foreign share must never validate');
			$this->assertContains(
				$result['reason'] ?? null,
				['not_intravox_share', 'page_not_found'],
				'unexpected refusal reason: ' . var_export($result['reason'] ?? null, true)
			);
		} finally {
			$this->shareManager()->deleteShare($share);
			$outsideFile->delete();
		}
	}
}
