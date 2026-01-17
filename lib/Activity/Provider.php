<?php
declare(strict_types=1);

namespace OCA\IntraVox\Activity;

use OCP\Activity\IEvent;
use OCP\Activity\IProvider;
use OCP\IL10N;
use OCP\IURLGenerator;
use Psr\Log\LoggerInterface;

/**
 * Activity Provider for IntraVox
 *
 * Formats IntraVox activity events for display in Nextcloud's activity stream.
 * Supports page view, edit, create, and delete events.
 */
class Provider implements IProvider {
    public function __construct(
        private IL10N $l10n,
        private IURLGenerator $urlGenerator,
        private LoggerInterface $logger
    ) {}

    /**
     * @param string $language
     * @param IEvent $event
     * @param IEvent|null $previousEvent
     * @return IEvent
     * @throws \InvalidArgumentException
     */
    public function parse($language, IEvent $event, ?IEvent $previousEvent = null): IEvent {
        if ($event->getApp() !== 'intravox') {
            throw new \InvalidArgumentException('Unknown app');
        }

        // Set the app icon
        $event->setIcon($this->urlGenerator->imagePath('intravox', 'app.svg'));

        $subject = $event->getSubject();
        $params = $event->getSubjectParameters();

        switch ($subject) {
            case 'page_viewed':
                $event->setParsedSubject(
                    $this->l10n->t('You viewed "%s"', [$params['pageTitle'] ?? 'Untitled'])
                );
                break;

            case 'page_created':
                $event->setParsedSubject(
                    $this->l10n->t('You created page "%s"', [$params['pageTitle'] ?? 'Untitled'])
                );
                break;

            case 'page_updated':
                $event->setParsedSubject(
                    $this->l10n->t('You updated page "%s"', [$params['pageTitle'] ?? 'Untitled'])
                );
                break;

            case 'page_deleted':
                $event->setParsedSubject(
                    $this->l10n->t('You deleted page "%s"', [$params['pageTitle'] ?? 'Untitled'])
                );
                break;

            case 'comment_added':
                $event->setParsedSubject(
                    $this->l10n->t('You commented on "%s"', [$params['pageTitle'] ?? 'Untitled'])
                );
                break;

            case 'reaction_added':
                $event->setParsedSubject(
                    $this->l10n->t('You reacted to "%s"', [$params['pageTitle'] ?? 'Untitled'])
                );
                break;

            default:
                throw new \InvalidArgumentException('Unknown subject: ' . $subject);
        }

        // Add link to the page if available
        if (!empty($params['pageId'])) {
            $event->setLink($this->urlGenerator->linkToRoute(
                'intravox.page.showByUniqueId',
                ['uniqueId' => $params['pageId']]
            ));
        }

        return $event;
    }
}
