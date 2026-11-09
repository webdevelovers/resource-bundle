<?php

declare(strict_types=1);

namespace WebDevelovers\ResourceBundle\Tests\Index\DataProvider\Option;

use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Translation\TranslatableInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use WebDevelovers\ResourceBundle\Index\DataProvider\Option\DoctrineFilterOptionProvider;
use WebDevelovers\ResourceBundle\Index\Definition\Filter\EnumFilter;
use WebDevelovers\ResourceBundle\Index\Definition\IndexDefinition;
use WebDevelovers\ResourceBundle\Index\Definition\View\IndexViewDefinition;

final class DoctrineFilterOptionProviderTest extends TestCase
{
    public function testGetOptionsTranslatesTranslatableEnumCases(): void
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $translator = $this->createMock(TranslatorInterface::class);
        $translator
            ->method('trans')
            ->willReturnCallback(static fn (string $id): string => 'translated.' . $id);

        $provider = new DoctrineFilterOptionProvider($entityManager, $translator);

        $definition = new IndexDefinition(
            name: 'wd.member_index',
            resourceClass: DummyMember::class,
            filters: ['status' => new EnumFilter('status', DummyTranslatableStatus::class, field: 'status')],
            views: ['table' => new IndexViewDefinition('table', 'table')],
        );

        $options = $provider->getOptions($definition, 'status');

        self::assertSame(
            [
                ['value' => 'active', 'label' => 'translated.member.status.active'],
                ['value' => 'deceased', 'label' => 'translated.member.status.deceased'],
            ],
            $options,
        );
    }

    public function testGetOptionsUsesLabelMethodWhenEnumIsNotTranslatable(): void
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $translator = $this->createMock(TranslatorInterface::class);

        $provider = new DoctrineFilterOptionProvider($entityManager, $translator);

        $definition = new IndexDefinition(
            name: 'wd.member_index',
            resourceClass: DummyMember::class,
            filters: ['status' => new EnumFilter('status', DummyLabelOnlyStatus::class, field: 'status')],
            views: ['table' => new IndexViewDefinition('table', 'table')],
        );

        $options = $provider->getOptions($definition, 'status');

        self::assertSame(
            [
                ['value' => 'active', 'label' => 'Active status'],
                ['value' => 'inactive', 'label' => 'Inactive status'],
            ],
            $options,
        );
    }

    public function testGetOptionsFallsBackToRawValueWhenEnumHasNoLabel(): void
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $translator = $this->createMock(TranslatorInterface::class);

        $provider = new DoctrineFilterOptionProvider($entityManager, $translator);

        $definition = new IndexDefinition(
            name: 'wd.member_index',
            resourceClass: DummyMember::class,
            filters: ['status' => new EnumFilter('status', DummyPlainStatus::class, field: 'status')],
            views: ['table' => new IndexViewDefinition('table', 'table')],
        );

        $options = $provider->getOptions($definition, 'status');

        self::assertSame(
            [
                ['value' => 'active', 'label' => 'active'],
                ['value' => 'inactive', 'label' => 'inactive'],
            ],
            $options,
        );
    }
}

final class DummyMember
{
}

enum DummyTranslatableStatus: string implements TranslatableInterface
{
    case Active = 'active';
    case Deceased = 'deceased';

    public function label(): string
    {
        return 'member.status.' . $this->value;
    }

    public function trans(TranslatorInterface $translator, string|null $locale = null): string
    {
        return $translator->trans($this->label(), locale: $locale);
    }
}

enum DummyLabelOnlyStatus: string
{
    case Active = 'active';
    case Inactive = 'inactive';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Active status',
            self::Inactive => 'Inactive status',
        };
    }
}

enum DummyPlainStatus: string
{
    case Active = 'active';
    case Inactive = 'inactive';
}
