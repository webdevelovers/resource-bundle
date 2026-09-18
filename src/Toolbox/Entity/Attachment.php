<?php

declare(strict_types=1);

namespace WebDevelovers\ResourceBundle\Toolbox\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Safe\DateTime;
use Symfony\Component\HttpFoundation\File\File;
use WebDevelovers\ResourceBundle\Model\ResourceReferenceDoctrineFields;
use WebDevelovers\ResourceBundle\ResourceReference;
use WebDevelovers\ResourceBundle\Toolbox\Repository\AttachmentRepository;
use WebDevelovers\ResourceModels\Invariant\TimestampableInterface;
use WebDevelovers\ResourceModels\Invariant\TimestampableTrait;
use WebDevelovers\ResourceModels\Invariant\UUIDIdentifiableInterface;
use WebDevelovers\ResourceModels\Invariant\UUIDIdentifiableTrait;

#[ORM\Entity(repositoryClass: AttachmentRepository::class)]
#[ORM\Table(name: 'toolbox_attachment')]
#[Vich\Uploadable]
class Attachment implements TimestampableInterface, UUIDIdentifiableInterface
{
    use ResourceReferenceDoctrineFields;
    use TimestampableTrait;
    use UUIDIdentifiableTrait;

    public function __construct(ResourceReference $resourceReference)
    {
        $this->initializeID();
        $this->initializeTimestamps();

        $this->setFromResourceReference($resourceReference);
    }

    #[Vich\UploadableField(
        mapping: 'toolbox_attachment',
        fileNameProperty: 'fileName',
        size: 'fileSize',
        mimeType: 'mimeType',
        originalName: 'originalName'
    )]
    public File | null $file = null {
        set(null | File $value) {
            $this->file = $value;
            if ($value !== null) {
                $this->updatedAt = new DateTime();
            }
        }
    }

    #[ORM\Column(type: Types::STRING, nullable: true)]
    public string|null $fileName = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    public int|null $fileSize = null;

    #[ORM\Column(type: Types::STRING, nullable: true)]
    public string|null $mimeType = null;

    #[ORM\Column(type: Types::STRING, nullable: true)]
    public string|null $originalName = null;
}
