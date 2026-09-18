<?php

declare(strict_types=1);

namespace WebDevelovers\ResourceBundle\CRUD;

use WebDevelovers\ResourceBundle\ResourceInterface;

interface DTOMapperInterface
{
    /** @param class-string<ResourceInterface>|ResourceInterface $resource */
    public function mapDTOToResource(object $dto, string|ResourceInterface $resource): ResourceInterface;

    /** @param class-string<object> $dtoClass */
    public function mapResourceToDTO(ResourceInterface $resource, string $dtoClass): object;
}

