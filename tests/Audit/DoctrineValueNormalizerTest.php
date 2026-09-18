<?php

declare(strict_types=1);

namespace WebDevelovers\ResourceBundle\Tests\Audit;

use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\TestCase;
use Stringable;
use Symfony\Component\Uid\Uuid;
use WebDevelovers\ResourceBundle\Audit\DoctrineValueNormalizer;

final class DoctrineValueNormalizerTest extends TestCase
{
    public function testNormalizeScalarAndNullValues(): void
    {
        $normalizer = new DoctrineValueNormalizer();

        self::assertNull($normalizer->normalize(null));
        self::assertSame('foo', $normalizer->normalize('foo'));
        self::assertSame(42, $normalizer->normalize(42));
        self::assertSame(12.5, $normalizer->normalize(12.5));
        self::assertTrue($normalizer->normalize(true));
    }

    public function testNormalizeComplexValues(): void
    {
        $normalizer = new DoctrineValueNormalizer();
        $uuid = Uuid::fromString('4c36b7ee-c0cc-4cb6-af8b-cc4fdde9c873');
        $date = new DateTimeImmutable('2026-01-02T03:04:05+00:00');

        $value = [
            'uuid' => $uuid,
            'date' => $date,
            'collection' => new ArrayCollection([
                new class () implements Stringable {
                    public function __toString(): string
                    {
                        return 'stringable-value';
                    }
                },
            ]),
            'entity' => new class () {
                public function getId(): int
                {
                    return 10;
                }
            },
        ];

        self::assertSame([
            'uuid' => '4c36b7ee-c0cc-4cb6-af8b-cc4fdde9c873',
            'date' => '2026-01-02T03:04:05+00:00',
            'collection' => ['stringable-value'],
            'entity' => [
                'id' => 10,
                'class' => $value['entity']::class,
            ],
        ], $normalizer->normalize($value));
    }

    public function testNormalizeObjectWithoutSupportedRepresentationUsesDebugType(): void
    {
        $normalizer = new DoctrineValueNormalizer();

        self::assertSame('stdClass', $normalizer->normalize(new \stdClass()));
    }
}
