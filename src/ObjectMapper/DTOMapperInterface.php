<?php

declare(strict_types=1);

namespace WebDevelovers\ResourceBundle\ObjectMapper;

use WebDevelovers\ResourceBundle\ResourceInterface;

interface DTOMapperInterface
{
    /** @param class-string<ResourceInterface>|ResourceInterface $resource */
    public function mapDTOToResource(object $dto, string|ResourceInterface $resource): ResourceInterface;

    /** @param class-string<object>|object $dto */
    public function mapResourceToDTO(ResourceInterface $resource, string|object $dto): object;
}

