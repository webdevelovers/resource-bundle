<?php

declare(strict_types=1);

namespace WebDevelovers\ResourceBundle\Controller\Parameters;

use Symfony\Component\HttpFoundation\Request;

final class RequestParameterProvider
{
    public static function provide(Request $request, string $key, mixed $default = null): mixed
    {
        $result = $request->attributes->get($key, $request);

        if ($request !== $result) {
            return $result;
        }

        if ($request->query->has($key)) {
            return $request->query->all()[$key];
        }

        if ($request->request->has($key)) {
            return $request->request->all()[$key];
        }

        return $default;
    }
}
