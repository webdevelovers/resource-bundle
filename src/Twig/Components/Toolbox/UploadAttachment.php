<?php

declare(strict_types=1);

namespace WebDevelovers\ResourceBundle\Twig\Components\Toolbox;

use Doctrine\ORM\EntityManagerInterface;
use LogicException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\ComponentToolsTrait;
use Symfony\UX\LiveComponent\DefaultActionTrait;

use WebDevelovers\ResourceBundle\Metadata\MetadataRegistryInterface;
use WebDevelovers\ResourceBundle\ResourceInterface;
use WebDevelovers\ResourceBundle\Toolbox\ToolboxManagerInterface;
use function assert;
use function count;

#[AsLiveComponent(name: 'Toolbox:UploadAttachment', template: '@WebDeveloversResource/components/toolbox/UploadAttachment.html.twig')]
final class UploadAttachment extends AbstractController
{
    use ComponentToolsTrait;
    use DefaultActionTrait;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly MetadataRegistryInterface $registry,
        private readonly ToolboxManagerInterface $toolboxManager,
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

    /** @var list<string> */
    #[LiveProp]
    public array $uploadErrors = [];

    #[LiveProp]
    public int $uploadedFilesCount = 0;

    #[LiveAction]
    public function save(Request $request): void
    {
        $this->uploadErrors = [];
        $this->uploadedFilesCount = 0;

        $files = $request->files->all('files');
        if ($files === []) {
            $singleFile = $request->files->get('file');
            if ($singleFile instanceof UploadedFile) {
                $files = [$singleFile];
            }
        }

        if ($files === []) {
            return;
        }

        $metadata = $this->registry->get($this->resourceType);
        /** @var class-string $class */
        $class = $metadata->getClass('model');
        $resource = $this->entityManager->getRepository($class)->find($this->resourceID);
        if ($resource === null) {
            throw new LogicException('The specified resource could not be found.');
        }

        assert($resource instanceof ResourceInterface);

        foreach ($files as $file) {
            if (! $file instanceof UploadedFile) {
                continue;
            }

            if (! $file->isValid()) {
                $this->uploadErrors[] = $file->getErrorMessage();

                continue;
            }

            $this->toolboxManager->uploadAttachment($file, $resource);
            ++$this->uploadedFilesCount;
        }

        if ($this->uploadedFilesCount > 0) {
            $this->emit('attachmentUpdate');
        }

        if (count($this->uploadErrors) > 0) {
            throw new UnprocessableEntityHttpException('Validation failed.');
        }

        if ($this->uploadedFilesCount <= 0) {
            return;
        }

        $this->dispatchBrowserEvent('offcanvas:close', ['id' => 'attachmentUploadOffcanvas']);
    }
}
