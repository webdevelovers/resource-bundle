<?php

declare(strict_types=1);

namespace WebDevelovers\ResourceBundle\Controller\Parameters;

use Symfony\Component\HttpFoundation\ParameterBag;

class Parameters extends ParameterBag
{
    public function get(string $key, mixed $default = null): mixed
    {
        $result = parent::get($key, $default);

        if ($result === null && $default !== null && $this->has($key)) {
            $result = $default;
        }

        return $result;
    }
}
