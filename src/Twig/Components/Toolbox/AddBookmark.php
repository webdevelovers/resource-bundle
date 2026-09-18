<?php

declare(strict_types=1);

namespace WebDevelovers\ResourceBundle\Twig\Components\Toolbox;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\ComponentToolsTrait;
use Symfony\UX\LiveComponent\DefaultActionTrait;

use WebDevelovers\ResourceBundle\ResourceReference;
use WebDevelovers\ResourceBundle\Security\CurrentUserProviderInterface;
use WebDevelovers\ResourceBundle\Toolbox\Entity\Bookmark;
use function assert;

//TODO: add maximum items parameter
#[AsLiveComponent(name: 'Toolbox:AddBookmark', template: '@WebDeveloversResource/components/toolbox/AddBookmark.html.twig')]
final class AddBookmark
{
    use ComponentToolsTrait;
    use DefaultActionTrait;

    public function __construct(
        private readonly CurrentUserProviderInterface $currentUserProvider,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    #[LiveProp]
    public string $resourceID;

    #[LiveProp]
    public string $resourceName;

    #[LiveProp]
    public string $resourceType;

    #[LiveProp]
    public string|null $section;

    #[LiveAction]
    public function addBookmark(): void
    {
        $user = $this->getUser();
        $resourceReference = new ResourceReference(
            $this->resourceID,
            $this->resourceName,
            $this->resourceType,
        );

        if ($this->bookmarkExists()) {
            return;
        }

        $bookmark = new Bookmark($user, $resourceReference);
        $bookmark->section = $this->section;

        $this->entityManager->persist($bookmark);
        $this->entityManager->flush();

        $this->emit('bookmarkAdded');
    }

    public function bookmarkExists(): bool
    {
        $user = $this->getUser();
        assert($user instanceof UserInterface);

        $bookmark = $this->entityManager->getRepository(Bookmark::class)->findOneBy([
            'user' => $user->id->toBinary(),
            'subject' => $this->resourceID,
        ]);

        return $bookmark !== null;
    }

    public function getUser(): UserInterface
    {
        $user = $this->currentUserProvider->requireUser();
        assert($user instanceof UserInterface);

        return $user;
    }
}
