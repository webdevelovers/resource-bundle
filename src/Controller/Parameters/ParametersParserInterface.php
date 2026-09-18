<?php

declare(strict_types=1);

namespace WebDevelovers\ResourceBundle\Controller\Parameters;

use Symfony\Component\HttpFoundation\Request;

interface ParametersParserInterface
{
    /**
     * @param array<string,mixed> $parameters
     *
     * @return array<string,mixed>
     */
    public function parseRequestValues(array $parameters, Request $request): array;
}
