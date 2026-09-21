<?php

declare(strict_types=1);

namespace WebDevelovers\ResourceBundle\Twig\Components\Toolbox;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;
use RuntimeException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Uid\Uuid;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveArg;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

use WebDevelovers\ResourceBundle\ResourceReference;
use WebDevelovers\ResourceBundle\Security\CurrentUserProviderInterface;
use WebDevelovers\ResourceBundle\Security\DoctrineUserClassResolver;
use WebDevelovers\ResourceBundle\Toolbox\Entity\Follower;
use WebDevelovers\ResourceBundle\Toolbox\ToolboxManagerInterface;
use function assert;

#[AsLiveComponent(name: 'Toolbox:Followers', template: '@WebDeveloversResource/components/toolbox/Followers.html.twig')]
final class Followers
{
    use DefaultActionTrait;

    #[LiveProp]
    public string $resourceID;

    #[LiveProp]
    public string $resourceName;

    #[LiveProp]
    public string $resourceType;

    #[LiveProp]
    public string|null $section;

    public function __construct(
        private readonly CurrentUserProviderInterface $currentUserProvider,
        private readonly EntityManagerInterface $entityManager,
        private readonly DoctrineUserClassResolver $userClassResolver,
        private readonly ToolboxManagerInterface $toolboxManager,
    ) {
    }

    /** @return Follower[] */
    public function getFollowers(): array
    {
        return $this->toolboxManager->getFollowers(Uuid::fromString($this->resourceID));
    }

    #[LiveAction]
    public function followToggle(): void
    {
        $reference = new ResourceReference($this->resourceID, $this->resourceName, $this->resourceType);
        $user = $this->currentUserProvider->requireUser();

        if (! $this->toolboxManager->isFollowing($reference, $user)) {
            $this->toolboxManager->addFollower($reference, $user);
        } else {
            $this->toolboxManager->removeFollower($reference, $user);
        }
    }

    #[LiveAction]
    public function removeFollower(
        #[LiveArg]
        string $id,
    ): void {
        try {
            $uuid = Uuid::fromString($id);
        } catch (InvalidArgumentException) {
            return;
        }

        $userClass = $this->userClassResolver->resolve();
        $user = $this->entityManager->getRepository($userClass)->find($uuid);
        if ($user === null) {
            return;
        }

        $currentUser = $this->currentUserProvider->getUser();
        if ($currentUser === null) {
            return;
        }

        assert($user instanceof UserInterface);
        assert($currentUser instanceof UserInterface);

        $reference = new ResourceReference($this->resourceID, $this->resourceName, $this->resourceType);
        $this->toolboxManager->removeFollower($reference, $user);
    }

    public function isFollowing(): bool
    {
        $followers = new ArrayCollection($this->getFollowers());
        $users = $followers->map(static function (Follower $follower) {
            return $follower->user;
        });

        return $users->contains($this->currentUserProvider->requireUser());
    }
}
