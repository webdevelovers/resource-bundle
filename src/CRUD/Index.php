<?php

declare(strict_types=1);

namespace WebDevelovers\ResourceBundle\CRUD;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use WebDevelovers\ResourceBundle\Controller\Renderer\RendererInterface;
use WebDevelovers\ResourceBundle\Index\Registry\IndexRegistryInterface;
use WebDevelovers\ResourceBundle\Index\Resolver\IndexStateResolverInterface;
use WebDevelovers\ResourceBundle\Index\State\IndexStateNormalizerInterface;
use WebDevelovers\ResourceBundle\RequestConfiguration\RequestConfiguration;
use WebDevelovers\ResourceBundle\Security\AuthorizationCheckerInterface;

final class Index extends AbstractController
{
    public function __invoke(
        Request $request,
        RequestConfiguration $configuration,
        AuthorizationCheckerInterface $authorizationChecker,
        IndexRegistryInterface $indexRegistry,
        IndexStateResolverInterface $indexStateResolver,
        IndexStateNormalizerInterface $indexStateNormalizer,
        RendererInterface $renderer,
    ): Response {
        $authorizationChecker->denyAccessUnlessGranted(CRUDEvent::INDEX->value, $configuration);

        $metadata = $configuration->metadata;
        $indexName = $configuration->getIndex() ?? $metadata->getAlias();
        $definition = $indexRegistry->get($indexName);

        $state = $indexStateResolver->resolve($request, $definition);
        $state = $indexStateNormalizer->normalize($state, $definition);

        return $renderer->render($configuration, CRUDEvent::INDEX->value, [
            'configuration' => $configuration,
            'metadata' => $metadata,
            'index' => $definition,
            'index_state' => $state,
            'index_component_context' => $configuration->getIndexComponentContext(
                defaultView: $definition->getDefaultView(),
                template: $configuration->getTemplate(CRUDEvent::INDEX->value),
            ),
        ]);
    }
}
