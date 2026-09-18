<?php

declare(strict_types=1);

namespace WebDevelovers\ResourceBundle\Twig\Components\Toolbox;

use Doctrine\ORM\EntityManagerInterface;
use RuntimeException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveArg;
use Symfony\UX\LiveComponent\Attribute\LiveListener;
use Symfony\UX\LiveComponent\DefaultActionTrait;

use WebDevelovers\ResourceBundle\Security\CurrentUserProviderInterface;
use WebDevelovers\ResourceBundle\Toolbox\Entity\Bookmark;
use WebDevelovers\ResourceBundle\Toolbox\ToolboxManagerInterface;
use function assert;

//TODO: add maximum items parameter
#[AsLiveComponent(name: 'Toolbox:Bookmarks', template: '@WebDeveloversResource/components/toolbox/Bookmarks.html.twig')]
final class Bookmarks
{
    use DefaultActionTrait;

    public function __construct(
        private readonly CurrentUserProviderInterface $currentUserProvider,
        private readonly EntityManagerInterface $entityManager,
        private readonly ToolboxManagerInterface $toolboxManager,
    ) {
    }

    /** @return Bookmark[] */
    public function getBookmarks(): array
    {
        return $this->toolboxManager->getBookmarks($this->currentUserProvider->requireUser());
    }

    #[LiveListener('bookmarkAdded')]
    public function bookmarkAdded(): void
    {
    }

    #[LiveAction]
    public function deleteBookmark(
        #[LiveArg]
        string $id,
    ): void {
        $entity = $this->entityManager->getRepository(Bookmark::class)->find($id);
        if ($entity === null) {
            throw new RuntimeException('Unable to find bookmark with id: ' . $id);
        }

        $this->entityManager->remove($entity);
        $this->entityManager->flush();
    }
}
