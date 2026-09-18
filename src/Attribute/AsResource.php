<?php

declare(strict_types=1);

namespace WebDevelovers\ResourceBundle\Attribute;

use Attribute;
use WebDevelovers\ResourceBundle\CRUD\ApplyTransition;
use WebDevelovers\ResourceBundle\CRUD\Create;
use WebDevelovers\ResourceBundle\CRUD\Delete;
use WebDevelovers\ResourceBundle\CRUD\Index;
use WebDevelovers\ResourceBundle\CRUD\Show;
use WebDevelovers\ResourceBundle\CRUD\Update;

#[Attribute(Attribute::TARGET_CLASS)]
final readonly class AsResource
{
    /** @param array<string, class-string> $controllers */
    public function __construct(
        public string $alias,
        public array $controllers = [
            'apply_transition' => ApplyTransition::class,
            'create' => Create::class,
            'delete' => Delete::class,
            'index' => Index::class,
            'show' => Show::class,
            'update' => Update::class,
        ],
    ) {
    }

    /**
     * @param class-string $className
     *
     * @return array<string, mixed>
     */
    public function asConfiguration(string $className): array
    {
        return [
            'driver' => 'doctrine/orm',
            'classes' => [
                'model' => $className,
                'controller' => $this->controllers,
            ],
        ];
    }
}
