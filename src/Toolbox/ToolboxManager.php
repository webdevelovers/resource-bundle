<?php

declare(strict_types=1);

namespace WebDevelovers\ResourceBundle\Toolbox;

use DateTimeInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Uid\Uuid;
use WebDevelovers\ResourceBundle\Blame\BlameGeneratorInterface;
use WebDevelovers\ResourceBundle\Metadata\MetadataRegistryInterface;
use WebDevelovers\ResourceBundle\ResourceInterface;
use WebDevelovers\ResourceBundle\ResourceReference;
use WebDevelovers\ResourceBundle\Toolbox\Entity\Activity;
use WebDevelovers\ResourceBundle\Toolbox\Entity\ActivityType;
use WebDevelovers\ResourceBundle\Toolbox\Entity\Attachment;
use WebDevelovers\ResourceBundle\Toolbox\Entity\Bookmark;
use WebDevelovers\ResourceBundle\Toolbox\Entity\Follower;
use WebDevelovers\ResourceBundle\Toolbox\Entity\TimelineEntry;
use WebDevelovers\ResourceBundle\Toolbox\Repository\ActivityRepository;
use WebDevelovers\ResourceBundle\Toolbox\Repository\AttachmentRepository;
use WebDevelovers\ResourceBundle\Toolbox\Repository\BookmarkRepository;
use WebDevelovers\ResourceBundle\Toolbox\Repository\FollowerRepository;
use WebDevelovers\ResourceBundle\Toolbox\Repository\TimelineEntryRepository;
use function count;

readonly class ToolboxManager implements ToolboxManagerInterface
{
    public function __construct(
        private ActivityRepository $activityRepository,
        private AttachmentRepository $attachmentRepository,
        private BookmarkRepository $bookmarkRepository,
        private FollowerRepository $followerRepository,
        private TimelineEntryRepository $timelineEntryRepository,
        private MetadataRegistryInterface $registry,
        private BlameGeneratorInterface $blameGenerator,
    ) {
    }

    /** @return Activity[] */
    public function getActivities(Uuid $subjectId): array
    {
        return $this->activityRepository->getActivities($subjectId);
    }

    /** @return Attachment[] */
    public function getAttachments(Uuid $subjectId): array
    {
        return $this->attachmentRepository->getAttachments($subjectId);
    }

    public function hasAttachments(Uuid $subjectId): bool
    {
        return count($this->attachmentRepository->getAttachments($subjectId)) > 0;
    }

    /** @return Bookmark[] */
    public function getBookmarks(UserInterface $user): array
    {
        return $this->bookmarkRepository->getBookmarks($user);
    }

    /** @return Follower[] */
    public function getFollowers(Uuid $subjectId): array
    {
        return $this->followerRepository->getFollowers($subjectId);
    }

    public function isFollowing(ResourceReference $resourceReference, UserInterface $user): bool
    {
        return $this->followerRepository->isFollowing(
            resourceReference: $resourceReference,
            user: $user,
        );
    }

    public function addFollower(ResourceReference $resourceReference, UserInterface $user): void
    {
        $this->followerRepository->follow($resourceReference, $user);
    }

    public function removeFollower(ResourceReference $resourceReference, UserInterface $user): void
    {
        $this->followerRepository->unfollow($resourceReference, $user);
    }

    /** @return TimelineEntry[] */
    public function getTimeline(Uuid $subjectID): array
    {
        return $this->timelineEntryRepository->getTimeline($subjectID);
    }

    public function addNote(string $note, ResourceInterface $resource): TimelineEntry
    {
        $resourceReference = $this->getResourceReference($resource);
        $blame = $this->blameGenerator->generate();

        return $this->timelineEntryRepository->addNote($note, $resourceReference, $blame);
    }

    public function addActivity(
        string $summary,
        ActivityType $activityType,
        DateTimeInterface $dueDate,
        UserInterface $assignedTo,
        ResourceInterface $resource,
        string|null $description = null,
    ): void {
        $resourceReference = $this->getResourceReference($resource);

        $this->activityRepository->addActivity(
            summary: $summary,
            activityType: $activityType,
            dueDate: $dueDate,
            resourceReference: $resourceReference,
            user: $assignedTo,
            description: $description,
        );
    }

    public function uploadAttachment(UploadedFile $file, ResourceInterface $resource): void
    {
        $metadata = $this->registry->getByClass($resource::class);

        $resourceReference = new ResourceReference(
            $resource->id,
            (string) $resource,
            $metadata->getAlias(),
        );

        $this->attachmentRepository->addAttachment($file, $resourceReference);
    }

    private function getResourceReference(ResourceInterface $resource): ResourceReference
    {
        $metadata = $this->registry->getByClass($resource::class);

        return new ResourceReference(
            $resource->id,
            (string) $resource,
            $metadata->getAlias(),
        );
    }
}
