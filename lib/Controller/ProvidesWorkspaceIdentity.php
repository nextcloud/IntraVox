<?php
declare(strict_types=1);

namespace OCA\IntraVox\Controller;

/** Authenticated initial state, never a substitute for page/file ACL checks. */
trait ProvidesWorkspaceIdentity {
    private function provideWorkspaceIdentity(): void {
        $user = $this->userSession->getUser();
        // Nextcloud initial state rejects null. An empty object/array is
        // intentionally invalid to the client identity checker (fail closed).
        $identity = [];
        if ($user !== null) {
            try {
                $identity = [
                    'uid' => $user->getUID(),
                    'groups' => $this->groupManager?->getUserGroupIds($user) ?? [],
                ];
            } catch (\Throwable $e) {
                $this->logger->warning('Workspace identity unavailable; using the shared lens only');
            }
        }
        $this->initialState->provideInitialState('aglIdentity', $identity);
    }
}
