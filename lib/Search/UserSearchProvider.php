<?php
declare(strict_types=1);

namespace OCA\IntraVox\Search;

use OCA\IntraVox\Service\UserService;
use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\IUser;
use OCP\Search\IProvider;
use OCP\Search\ISearchQuery;
use OCP\Search\SearchResult;
use OCP\Search\SearchResultEntry;

class UserSearchProvider implements IProvider {
    private UserService $userService;
    private IL10N $l10n;
    private IURLGenerator $urlGenerator;

    public function __construct(
        UserService $userService,
        IL10N $l10n,
        IURLGenerator $urlGenerator
    ) {
        $this->userService = $userService;
        $this->l10n = $l10n;
        $this->urlGenerator = $urlGenerator;
    }

    public function getId(): string {
        return 'intravox_users';
    }

    public function getName(): string {
        return $this->l10n->t('People');
    }

    public function getOrder(string $route, array $routeParameters): int {
        // Show after IntraVox pages (-5 to -10) but before Files (0)
        if (str_starts_with($route, 'intravox.')) {
            return -8;
        }
        return -3;
    }

    public function search(IUser $user, ISearchQuery $query): SearchResult {
        $term = $query->getTerm();

        // Don't search for very short queries
        if (mb_strlen($term) < 2) {
            return SearchResult::complete(
                $this->l10n->t('People'),
                []
            );
        }

        try {
            $users = $this->userService->searchUsers($term, 10);
            $entries = [];

            foreach ($users as $userProfile) {
                // Build subline from role and organisation
                $subline = [];
                if (!empty($userProfile['role'])) {
                    $subline[] = $userProfile['role'];
                }
                if (!empty($userProfile['organisation'])) {
                    $subline[] = $userProfile['organisation'];
                }

                // Fallback to email if no role/organisation
                $sublineText = implode(' · ', $subline);
                if (empty($sublineText) && !empty($userProfile['email'])) {
                    $sublineText = $userProfile['email'];
                }

                // Generate avatar URL
                $avatarUrl = $this->urlGenerator->linkToRouteAbsolute(
                    'core.avatar.getAvatar',
                    ['userId' => $userProfile['uid'], 'size' => 64]
                );

                // Link to Nextcloud's native user profile page
                $profileUrl = $this->urlGenerator->linkToRouteAbsolute(
                    'core.ProfilePage.index',
                    ['targetUserId' => $userProfile['uid']]
                );

                $entry = new SearchResultEntry(
                    $avatarUrl,
                    $userProfile['displayName'] ?? $userProfile['uid'],
                    $sublineText,
                    $profileUrl,
                    'icon-user',
                    true // rounded avatar
                );

                $entries[] = $entry;
            }

            return SearchResult::complete(
                $this->l10n->t('People'),
                $entries
            );
        } catch (\Exception $e) {
            return SearchResult::complete(
                $this->l10n->t('People'),
                []
            );
        }
    }
}
