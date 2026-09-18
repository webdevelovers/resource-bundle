<?php

declare(strict_types=1);

namespace WebDevelovers\ResourceBundle;

use function is_object;
use function strrpos;
use function substr;

class ClassUtils
{
    /** Get the real class name of a class name that could be a proxy */
    public static function getRealClassName(string|object $value): string
    {
        $className = is_object($value) ? $value::class : $value;

        // __CG__: Doctrine Common Marker for Proxy (ODM < 2.0 and ORM < 3.0)
        $positionCg = strrpos($className, '\\__CG__\\');
        if ($positionCg !== false) {
            return substr($className, $positionCg + 8);
        }

        // __PM__: Ocramius Proxy Manager (ODM >= 2.0)
        $positionPm = strrpos($className, '\\__PM__\\');
        if ($positionPm === false) {
            return $className;
        }

        return substr($className, 0, $positionPm);
    }
}
