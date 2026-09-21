<?php

declare(strict_types=1);

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use WebDevelovers\ResourceBundle\Action\ResourceActionCollector;
use WebDevelovers\ResourceBundle\Audit\DoctrineTimelinePayloadExtractor;
use WebDevelovers\ResourceBundle\Audit\DoctrineTimelineSubscriber;
use WebDevelovers\ResourceBundle\Audit\DoctrineValueNormalizer;
use WebDevelovers\ResourceBundle\Blame\BlameGenerator;
use WebDevelovers\ResourceBundle\Blame\BlameGeneratorInterface;
use WebDevelovers\ResourceBundle\Controller\RedirectHandler;
use WebDevelovers\ResourceBundle\Controller\RedirectHandlerInterface;
use WebDevelovers\ResourceBundle\Controller\Parameters\ParametersParser;
use WebDevelovers\ResourceBundle\Controller\Parameters\ParametersParserInterface;
use WebDevelovers\ResourceBundle\Controller\Renderer\RendererInterface;
use WebDevelovers\ResourceBundle\Controller\Renderer\TwigRenderer;
use WebDevelovers\ResourceBundle\CRUD\DTOMapperInterface;
use WebDevelovers\ResourceBundle\CRUD\PropertyAccessDTOMapper;
use WebDevelovers\ResourceBundle\Command\DebugResourceCommand;
use WebDevelovers\ResourceBundle\Event\ResourceActionEventDispatcher;
use WebDevelovers\ResourceBundle\Event\ResourceActionEventDispatcherInterface;
use WebDevelovers\ResourceBundle\Index\DataProvider\DoctrineOrmIndexDataProvider;
use WebDevelovers\ResourceBundle\Index\DataProvider\IndexDataProviderInterface;
use WebDevelovers\ResourceBundle\Index\DataProvider\Option\DoctrineFilterOptionProvider;
use WebDevelovers\ResourceBundle\Index\DataProvider\Option\FilterOptionProviderInterface;
use WebDevelovers\ResourceBundle\Index\Factory\ResourceIndexDefinitionFactory;
use WebDevelovers\ResourceBundle\Index\Registry\IndexRegistry;
use WebDevelovers\ResourceBundle\Index\Registry\IndexRegistryInterface;
use WebDevelovers\ResourceBundle\Index\Resolver\IndexStateResolver;
use WebDevelovers\ResourceBundle\Index\Resolver\IndexStateResolverInterface;
use WebDevelovers\ResourceBundle\Index\State\IndexStateNormalizer;
use WebDevelovers\ResourceBundle\Index\State\IndexStateNormalizerInterface;
use WebDevelovers\ResourceBundle\Index\View\Field\FieldRenderer;
use WebDevelovers\ResourceBundle\Index\View\Field\FieldRendererInterface;
use WebDevelovers\ResourceBundle\Index\View\Field\FieldValueResolver;
use WebDevelovers\ResourceBundle\Metadata\MetadataRegistry;
use WebDevelovers\ResourceBundle\Metadata\MetadataRegistryInterface;
use WebDevelovers\ResourceBundle\Messenger\PersistenceMiddleware;
use WebDevelovers\ResourceBundle\Messenger\ResourceMessageBus;
use WebDevelovers\ResourceBundle\Messenger\ResourceMessageBusInterface;
use WebDevelovers\ResourceBundle\Index\View\IndexViewFactory;
use WebDevelovers\ResourceBundle\Index\View\IndexViewFactoryInterface;
use WebDevelovers\ResourceBundle\RequestConfiguration\RequestConfigurationFactory;
use WebDevelovers\ResourceBundle\RequestConfiguration\RequestConfigurationFactoryInterface;
use WebDevelovers\ResourceBundle\Routing\ResourceLoader;
use WebDevelovers\ResourceBundle\Routing\RouteFactory;
use WebDevelovers\ResourceBundle\Routing\RouteFactoryInterface;
use WebDevelovers\ResourceBundle\Routing\RouterAvailabilityResolver;
use WebDevelovers\ResourceBundle\Security\AuthorizationChecker;
use WebDevelovers\ResourceBundle\Security\AuthorizationCheckerInterface;
use WebDevelovers\ResourceBundle\Security\CurrentUserProviderInterface;
use WebDevelovers\ResourceBundle\Security\DoctrineUserClassResolver;
use WebDevelovers\ResourceBundle\Security\SymfonyCurrentUserProvider;
use WebDevelovers\ResourceBundle\Twig\Extension\AttachmentExtension;
use WebDevelovers\ResourceBundle\Twig\Extension\IndexExtension;
use WebDevelovers\ResourceBundle\Twig\Extension\ResourceExtension;
use WebDevelovers\ResourceBundle\Twig\Extension\ToolboxExtension;
use WebDevelovers\ResourceBundle\Twig\Runtime\AttachmentExtensionRuntime;
use WebDevelovers\ResourceBundle\Twig\Runtime\ResourceExtensionRuntime;
use WebDevelovers\ResourceBundle\Toolbox\ToolboxManager;
use WebDevelovers\ResourceBundle\Toolbox\ToolboxManagerInterface;
use WebDevelovers\ResourceBundle\Twig\Components\Toolbox\Form\PlanActivityType;
use WebDevelovers\ResourceBundle\Toolbox\Repository\ActivityRepository;
use WebDevelovers\ResourceBundle\Toolbox\Repository\AttachmentRepository;
use WebDevelovers\ResourceBundle\Toolbox\Repository\BookmarkRepository;
use WebDevelovers\ResourceBundle\Toolbox\Repository\FollowerRepository;
use WebDevelovers\ResourceBundle\Toolbox\Repository\TimelineEntryRepository;
use WebDevelovers\ResourceBundle\Toolbox\Timeline\Formatter\DoctrineRelationValueFormatter;
use WebDevelovers\ResourceBundle\Toolbox\Timeline\Formatter\IsoDateTimeValueFormatter;
use WebDevelovers\ResourceBundle\Toolbox\Timeline\Formatter\MoneyValueFormatter;
use WebDevelovers\ResourceBundle\Toolbox\Timeline\Formatter\ScalarValueFormatter;
use WebDevelovers\ResourceBundle\Toolbox\Timeline\Formatter\TimelineValueFormatterRegistry;
use WebDevelovers\ResourceBundle\Toolbox\Timeline\TimelineDiffPresenter;
use WebDevelovers\ResourceBundle\Twig\Runtime\ToolboxExtensionRuntime;
use WebDevelovers\ResourceBundle\ValueResolver\RequestConfigurationValueResolver;
use WebDevelovers\ResourceBundle\ValueResolver\ResourceValueResolver;

return static function (ContainerConfigurator $container): void {
    $services = $container->services()
        ->defaults()
        ->autowire()
        ->autoconfigure();

    // Core registries and factories.
    $services->set(ResourceActionCollector::class);
    $services->set(MetadataRegistry::class);
    $services->set(RequestConfigurationFactory::class);
    $services->set(RouteFactory::class);

    // Index services.
    $services->set(ResourceIndexDefinitionFactory::class);
    $services->set(IndexRegistry::class);
    $services->set(IndexStateResolver::class);
    $services->set(IndexStateNormalizer::class);
    $services->set(DoctrineOrmIndexDataProvider::class);
    $services->set(DoctrineFilterOptionProvider::class);
    $services->set(IndexViewFactory::class);
    $services->set(FieldValueResolver::class);
    $services->set(FieldRenderer::class);

    // Console commands.
    $services->set(DebugResourceCommand::class);

    // Auditing.
    $services->set(BlameGenerator::class);
    $services->set(DoctrineValueNormalizer::class);
    $services->set(DoctrineTimelinePayloadExtractor::class);
    $services->set(DoctrineTimelineSubscriber::class);

    // Toolbox.
    $services->set(ToolboxManager::class);
    $services->set(ActivityRepository::class);
    $services->set(AttachmentRepository::class);
    $services->set(BookmarkRepository::class);
    $services->set(FollowerRepository::class);
    $services->set(TimelineEntryRepository::class);
    $services->set(TimelineDiffPresenter::class);
    $services->set(PlanActivityType::class);
    $services->set(TimelineValueFormatterRegistry::class);
    $services->set(DoctrineRelationValueFormatter::class)
        ->tag('wd.resource.timeline_value_formatter', ['priority' => 100]);
    $services->set(IsoDateTimeValueFormatter::class)
        ->tag('wd.resource.timeline_value_formatter');
    $services->set(MoneyValueFormatter::class)
        ->tag('wd.resource.timeline_value_formatter');
    $services->set(ScalarValueFormatter::class)
        ->tag('wd.resource.timeline_value_formatter', ['priority' => -255]);

    // Routing and request parsing.
    $services->set(ResourceLoader::class)
        ->arg('$taggedResourceConfigurations', '%wd.resource_routes%')
        ->tag('routing.loader');
    $services->set(RouterAvailabilityResolver::class);
    $services->set(ParametersParser::class);

    // Rendering, security and CRUD helpers.
    $services->set(TwigRenderer::class);
    $services->set(RedirectHandler::class);
    $services->set(AuthorizationChecker::class);
    $services->set(SymfonyCurrentUserProvider::class);
    $services->set(DoctrineUserClassResolver::class);
    $services->set(PropertyAccessDTOMapper::class);

    // Twig/Live components.
    $services->load('WebDevelovers\\ResourceBundle\\Twig\\Components\\', __DIR__ . '/../src/Twig/Components/');
    $services->set(AttachmentExtension::class);
    $services->set(IndexExtension::class);
    $services->set(ResourceExtension::class);
    $services->set(AttachmentExtensionRuntime::class);
    $services->set(ResourceExtensionRuntime::class);
    $services->set(ToolboxExtension::class);
    $services->set(ToolboxExtensionRuntime::class);

    // Events and messenger integration.
    $services->set(ResourceActionEventDispatcher::class);
    $services->set(ResourceMessageBus::class);
    $services->set(PersistenceMiddleware::class);

    // Controller argument value resolvers.
    $services->set(RequestConfigurationValueResolver::class)
        ->tag('controller.argument_value_resolver');
    $services->set(ResourceValueResolver::class)
        ->tag('controller.argument_value_resolver');

    // Public aliases and interface bindings.
    $services->alias(MetadataRegistryInterface::class, MetadataRegistry::class);
    $services->alias('wd.resource_registry', MetadataRegistry::class);

    $services->alias(BlameGeneratorInterface::class, BlameGenerator::class);
    $services->alias(ToolboxManagerInterface::class, ToolboxManager::class);
    $services->alias('wd.toolbox_manager', ToolboxManager::class)->public();

    $container->parameters()->set('wd.resource_routes', []);

    $services->alias(RequestConfigurationFactoryInterface::class, RequestConfigurationFactory::class);
    $services->alias('wd.request_configuration_factory', RequestConfigurationFactory::class);
    $services->alias(RouteFactoryInterface::class, RouteFactory::class);
    $services->alias(ParametersParserInterface::class, ParametersParser::class);
    $services->alias(RendererInterface::class, TwigRenderer::class);
    $services->alias(RedirectHandlerInterface::class, RedirectHandler::class);
    $services->alias(AuthorizationCheckerInterface::class, AuthorizationChecker::class);
    $services->alias(CurrentUserProviderInterface::class, SymfonyCurrentUserProvider::class);
    $services->alias(DTOMapperInterface::class, PropertyAccessDTOMapper::class);
    $services->alias(ResourceActionEventDispatcherInterface::class, ResourceActionEventDispatcher::class);
    $services->alias(ResourceMessageBusInterface::class, ResourceMessageBus::class);
    $services->alias(IndexRegistryInterface::class, IndexRegistry::class);
    $services->alias(IndexStateResolverInterface::class, IndexStateResolver::class);
    $services->alias(IndexStateNormalizerInterface::class, IndexStateNormalizer::class);
    $services->alias(IndexDataProviderInterface::class, DoctrineOrmIndexDataProvider::class);
    $services->alias(FilterOptionProviderInterface::class, DoctrineFilterOptionProvider::class);
    $services->alias(IndexViewFactoryInterface::class, IndexViewFactory::class);
    $services->alias(FieldRendererInterface::class, FieldRenderer::class);
};
