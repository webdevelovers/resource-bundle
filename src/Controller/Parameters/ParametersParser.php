<?php

declare(strict_types=1);

namespace WebDevelovers\ResourceBundle\Controller\Parameters;

use Symfony\Component\HttpFoundation\Request;

use function array_map;
use function assert;
use function explode;
use function is_array;
use function is_callable;
use function is_string;
use function str_starts_with;
use function substr;

final class ParametersParser implements ParametersParserInterface
{
    /**
     * @param array<string,mixed> $parameters
     *
     * @return array<string,mixed>
     */
    public function parseRequestValues(array $parameters, Request $request): array
    {
        return array_map(
            /**
             * @param mixed $parameter
             *
             * @return mixed
             */
            function ($parameter) use ($request) {
                if (is_array($parameter)) {
                    return $this->parseRequestValues($parameter, $request);
                }

                return $this->parseRequestValue($parameter, $request);
            },
            $parameters,
        );
    }

    private function parseRequestValue(mixed $parameter, Request $request): mixed
    {
        if (! is_string($parameter)) {
            return $parameter;
        }

        if (str_starts_with($parameter, '$')) {
            return RequestParameterProvider::provide($request, substr($parameter, 1));
        }

        if (str_starts_with($parameter, '!!')) {
            return $this->parseRequestValueTypecast($parameter, $request);
        }

        return $parameter;
    }

    private function parseRequestValueTypecast(string $parameter, Request $request): int|float|bool
    {
        [$typecast, $castedValue] = explode(' ', $parameter, 2);

        $castFunctionName = substr($typecast, 2) . 'val';
        assert(is_callable($castFunctionName));

        return $castFunctionName($this->parseRequestValue($castedValue, $request));
    }
}
