<?php

declare(strict_types=1);

namespace WebDevelovers\ResourceBundle\RequestConfiguration;

use InvalidArgumentException;
use Symfony\Component\HttpFoundation\Request;
use WebDevelovers\ResourceBundle\Metadata\MetadataInterface;

interface RequestConfigurationFactoryInterface
{
    /** @throws InvalidArgumentException */
    public function create(MetadataInterface $metadata, Request $request): RequestConfiguration;
}
