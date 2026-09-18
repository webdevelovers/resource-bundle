<?php

declare(strict_types=1);

namespace WebDevelovers\ResourceBundle\Twig\Components\Toolbox;

use Doctrine\ORM\EntityManagerInterface;
use RuntimeException;
use Symfony\Component\Uid\Uuid;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveArg;
use Symfony\UX\LiveComponent\Attribute\LiveListener;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\ComponentToolsTrait;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use WebDevelovers\ResourceBundle\Toolbox\Entity\TimelineEntry;
use WebDevelovers\ResourceBundle\Toolbox\ToolboxManagerInterface;

#[AsLiveComponent(name: 'Toolbox:Timeline', template: '@WebDeveloversResource/components/toolbox/Timeline.html.twig')]
final class Timeline
{
    private const bool SHOW_AUDIT_WHEN_NOTES_AVAILABLE_BY_DEFAULT = false;
    private const bool SHOW_AUDIT_WHEN_NOTES_UNAVAILABLE_BY_DEFAULT = true;
    use ComponentToolsTrait;
    use DefaultActionTrait;

    #[LiveProp]
    public string $resourceID;

    #[LiveProp]
    public string $resourceName;

    #[LiveProp]
    public string $resourceType;

    #[LiveProp]
    public string|null $section;

    #[LiveProp(writable: true)]
    public bool|null $showNotes = null;

    #[LiveProp(writable: true)]
    public bool|null $showAudit = null;

    public function __construct(
        private EntityManagerInterface $entityManager,
        private readonly ToolboxManagerInterface $toolboxManager,
        private readonly TranslatorInterface $translator,
    ) {
    }

    /** @return TimelineEntry[] */
    public function getTimeline(): array
    {
        return $this->toolboxManager->getTimeline(Uuid::fromString($this->resourceID));
    }

    /** @return TimelineEntry[] */
    public function getVisibleTimeline(): array
    {
        $this->initializeFilters();

        $visibleTimeline = [];
        foreach ($this->getTimeline() as $entry) {
            if ($entry->event === 'note') {
                if (! $this->showNotes) {
                    continue;
                }
            } elseif (! $this->showAudit) {
                continue;
            }

            $visibleTimeline[] = $entry;
        }

        return $visibleTimeline;
    }

    public function getNoteCount(): int
    {
        $count = 0;
        foreach ($this->getTimeline() as $entry) {
            if ($entry->event !== 'note') {
                continue;
            }

            ++$count;
        }

        return $count;
    }

    public function getAuditCount(): int
    {
        $count = 0;
        foreach ($this->getTimeline() as $entry) {
            if ($entry->event === 'note') {
                continue;
            }

            ++$count;
        }

        return $count;
    }

    #[LiveAction]
    public function toggleFilter(
        #[LiveArg]
        string $filter,
    ): void {
        $this->initializeFilters();

        if ($filter === 'notes') {
            $this->showNotes = ! $this->showNotes;

            return;
        }

        if ($filter !== 'audit') {
            return;
        }

        $this->showAudit = ! $this->showAudit;
    }

    #[LiveListener('timelineUpdate')]
    public function timelineUpdate(): void
    {
    }

    #[LiveAction]
    public function deleteEntry(
        #[LiveArg]
        string $id,
    ): void {
        $this->emit('show-confirmation-modal', [
            'message' => 'Sei sicuro di voler eliminare questo elemento?',
            'event' => 'deleteTimelineEntry',
            'context' => ['id' => $id],
        ]);
    }

    #[LiveListener('deleteTimelineEntry')]
    public function delete(
        #[LiveArg]
        string $id,
    ): void {
        //TODO: check if note?
        $entity = $this->entityManager->getRepository(TimelineEntry::class)->find($id);
        if ($entity === null) {
            throw new RuntimeException('Unable to find note with id: ' . $id);
        }

        $this->entityManager->remove($entity);
        $this->entityManager->flush();

        $this->dispatchBrowserEvent('modal:close');
    }

    private function initializeFilters(): void
    {
        if ($this->showNotes !== null && $this->showAudit !== null) {
            return;
        }

        $hasNotes = $this->getNoteCount() > 0;

        $this->showNotes ??= $hasNotes;
        $this->showAudit ??= $hasNotes
            ? self::SHOW_AUDIT_WHEN_NOTES_AVAILABLE_BY_DEFAULT
            : self::SHOW_AUDIT_WHEN_NOTES_UNAVAILABLE_BY_DEFAULT;
    }
}
