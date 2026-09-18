<?php

declare(strict_types=1);

namespace WebDevelovers\ResourceBundle\Toolbox;

use DateTimeInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Uid\Uuid;
use WebDevelovers\ResourceBundle\ResourceInterface;
use WebDevelovers\ResourceBundle\ResourceReference;
use WebDevelovers\ResourceBundle\Toolbox\Entity\Activity;
use WebDevelovers\ResourceBundle\Toolbox\Entity\ActivityType;
use WebDevelovers\ResourceBundle\Toolbox\Entity\Attachment;
use WebDevelovers\ResourceBundle\Toolbox\Entity\Bookmark;
use WebDevelovers\ResourceBundle\Toolbox\Entity\Follower;
use WebDevelovers\ResourceBundle\Toolbox\Entity\TimelineEntry;

interface ToolboxManagerInterface
{
    /** @return Activity[] */
    public function getActivities(Uuid $subjectId): array;

    /** @return Attachment[] */
    public function getAttachments(Uuid $subjectId): array;

    public function hasAttachments(Uuid $subjectId): bool;

    /** @return Bookmark[] */
    public function getBookmarks(UserInterface $user): array;

    /** @return Follower[] */
    public function getFollowers(Uuid $subjectId): array;

    public function isFollowing(ResourceReference $resourceReference, UserInterface $user): bool;

    public function addFollower(ResourceReference $resourceReference, UserInterface $user): void;

    public function removeFollower(ResourceReference $resourceReference, UserInterface $user): void;

    /** @return TimelineEntry[] */
    public function getTimeline(Uuid $subjectID): array;

    public function addNote(string $note, ResourceInterface $resource): TimelineEntry;

    public function addActivity(
        string $summary,
        ActivityType $activityType,
        DateTimeInterface $dueDate,
        UserInterface $assignedTo,
        ResourceInterface $resource,
        string|null $description = null,
    ): void;

    public function uploadAttachment(UploadedFile $file, ResourceInterface $resource): void;
}
