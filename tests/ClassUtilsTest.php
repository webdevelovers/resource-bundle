<?php

declare(strict_types=1);

namespace App\Tests\Resource;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use WebDevelovers\ResourceBundle\ClassUtils;

class ClassUtilsTest extends TestCase
{
    #[DataProvider('provideClassNameData')]
    public function testGetRealClassName(string|object $input, string $expected): void
    {
        $this->assertSame($expected, ClassUtils::getRealClassName($input));
    }

    /** @return iterable<string, array{0: string|object, 1: string}> */
    public static function provideClassNameData(): iterable
    {
        yield 'normal class name' => [
            'App\Entity\User',
            'App\Entity\User',
        ];

        yield 'class name with leading backslash' => [
            '\App\Entity\User',
            '\App\Entity\User',
        ];

        yield 'doctrine proxy ORM < 3.0' => [
            'Proxies\__CG__\App\Entity\User',
            'App\Entity\User',
        ];

        yield 'ocramius proxy manager' => [
            'App\Entity\User\__PM__\GeneratedClass\AnotherPart',
            'App\Entity\User',
        ];

        $object = new class {};
        yield 'anonymous object' => [
            $object,
            $object::class,
        ];
    }
}
