<?php

declare(strict_types=1);

namespace WebDevelovers\ResourceBundle\Twig\Components\Toolbox;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Uid\Uuid;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveArg;
use Symfony\UX\LiveComponent\Attribute\LiveListener;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\ComponentToolsTrait;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use WebDevelovers\ResourceBundle\ResourceReference;
use WebDevelovers\ResourceBundle\Toolbox\Entity\Attachment;
use WebDevelovers\ResourceBundle\Toolbox\ToolboxManagerInterface;

#[AsLiveComponent(name: 'Toolbox:AttachmentList', template: '@WebDeveloversResource/components/toolbox/AttachmentList.html.twig')]
final class AttachmentList
{
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

    #[LiveProp]
    public int $fileNameMaxLength = 55;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly ToolboxManagerInterface $toolboxManager,
    ) {
    }

    /** @return Attachment[] */
    public function getAttachments(): array
    {
        return $this->toolboxManager->getAttachments(Uuid::fromString($this->resourceID));
    }

    #[LiveAction]
    public function delete(
        #[LiveArg]
        string $id,
    ): void {
        $entity = $this->entityManager->getRepository(Attachment::class)->find($id);
        if ($entity === null) {
            return;
        }

        $this->entityManager->remove($entity);
        $this->entityManager->flush();

        $this->dispatchBrowserEvent('modal:close');
    }

    #[LiveListener('deleteAttachment')]
    public function deleteAttachment(
        #[LiveArg]
        string $id,
    ): void {
        $this->delete($id);
    }

    #[LiveListener('attachmentUpdate')]
    public function attachmentListUpdate(): void
    {
    }

    private function getResourceReference(): ResourceReference
    {
        return new ResourceReference($this->resourceID, $this->resourceName, $this->resourceType);
    }
}
