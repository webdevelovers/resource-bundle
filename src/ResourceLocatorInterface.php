<?php

declare(strict_types=1);

namespace WebDevelovers\ResourceBundle;

interface ResourceLocatorInterface
{
    public function getResource(ResourceReference $reference): ResourceInterface;
}
