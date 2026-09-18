<?php

declare(strict_types=1);

namespace WebDevelovers\ResourceBundle\Toolbox\Timeline\Formatter;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;
use Symfony\Component\Uid\Uuid;
use Throwable;
use WebDevelovers\ResourceBundle\Toolbox\Entity\TimelineEntry;

use function is_object;
use function is_string;
use function method_exists;

#[AsTaggedItem(priority: 100)]
final readonly class DoctrineRelationValueFormatter implements TimelineValueFormatterInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function supports(string $entityClass, string $field, mixed $value, TimelineEntry $entry): bool
    {
        if (! is_string($value) || ! Uuid::isValid($value)) {
            return false;
        }

        try {
            $metadata = $this->entityManager->getClassMetadata($entityClass);
        } catch (Throwable) {
            return false;
        }

        if (! $metadata->hasAssociation($field)) {
            return false;
        }

        if ($metadata->isCollectionValuedAssociation($field)) {
            return false;
        }

        return true;
    }

    public function format(string $entityClass, string $field, mixed $value, TimelineEntry $entry): string
    {
        $uuid = (string) $value;

        try {
            $metadata = $this->entityManager->getClassMetadata($entityClass);
            $targetClass = $metadata->getAssociationTargetClass($field);
            $target = $this->entityManager->getRepository($targetClass)->find($uuid);
        } catch (Throwable) {
            return $uuid;
        }

        if (is_object($target) && method_exists($target, '__toString')) {
            return (string) $target;
        }

        return $uuid;
    }
}
