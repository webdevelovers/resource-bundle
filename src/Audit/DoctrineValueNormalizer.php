<?php

declare(strict_types=1);

namespace WebDevelovers\ResourceBundle\Audit;

use DateTimeInterface;
use Doctrine\Common\Collections\Collection;
use Stringable;
use Symfony\Component\Uid\Uuid;

use function array_map;
use function get_debug_type;
use function is_array;
use function is_object;
use function method_exists;

final class DoctrineValueNormalizer
{
    public function normalize(mixed $value): mixed
    {
        if ($value === null || is_scalar($value)) {
            return $value;
        }

        if ($value instanceof Uuid) {
            return (string) $value;
        }

        if ($value instanceof DateTimeInterface) {
            return $value->format(DateTimeInterface::ATOM);
        }

        if ($value instanceof Collection) {
            return array_map($this->normalize(...), $value->toArray());
        }

        if (is_array($value)) {
            return array_map($this->normalize(...), $value);
        }

        if ($value instanceof Stringable) {
            return (string) $value;
        }

        if (is_object($value)) {
            if (method_exists($value, 'getId')) {
                return [
                    'id' => $this->normalize($value->getId()),
                    'class' => $value::class,
                ];
            }

            return get_debug_type($value);
        }

        return (string) $value;
    }
}
