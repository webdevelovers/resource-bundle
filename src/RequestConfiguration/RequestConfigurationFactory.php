<?php

declare(strict_types=1);

namespace WebDevelovers\ResourceBundle\RequestConfiguration;

use Symfony\Component\HttpFoundation\Request;

use WebDevelovers\ResourceBundle\Controller\Parameters\Parameters;
use WebDevelovers\ResourceBundle\Controller\Parameters\ParametersParserInterface;
use WebDevelovers\ResourceBundle\Metadata\MetadataInterface;
use WebDevelovers\ResourceBundle\Metadata\MetadataRegistryInterface;
use function array_merge;

final readonly class RequestConfigurationFactory implements RequestConfigurationFactoryInterface
{
    /** @param array<string, mixed> $defaultParameters */
    public function __construct(
        private ParametersParserInterface $parametersParser,
        private MetadataRegistryInterface $registry,
        private string $configurationClass = RequestConfiguration::class,
        private array $defaultParameters = [],
    ) {
    }

    public function create(MetadataInterface $metadata, Request $request): RequestConfiguration
    {
        $parameters = array_merge($this->defaultParameters, $request->attributes->get('_wd', []));
        $parameters = $this->parametersParser->parseRequestValues($parameters, $request);

        /** @var RequestConfiguration $configuration */
        $configuration = new $this->configurationClass($metadata, $request, new Parameters($parameters));

        return $configuration;
    }

    /** @param array<string, mixed> $parameters */
    public function createSystemOperation(
        string $resourceAlias,
        array $parameters = [],
    ): RequestConfiguration {
        $metadata = $this->registry->get($resourceAlias);
        $parameters = array_merge($parameters, [
            'context' => 'system',
            'is_system_operation' => true,
        ]);

        return new RequestConfiguration(
            metadata: $metadata,
            request: null,
            parameters: new Parameters($parameters),
        );
    }
}
