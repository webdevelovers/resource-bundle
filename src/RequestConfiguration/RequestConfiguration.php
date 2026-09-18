<?php

declare(strict_types=1);

namespace WebDevelovers\ResourceBundle\RequestConfiguration;

use RuntimeException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PropertyAccess\PropertyAccess;
use WebDevelovers\ResourceBundle\Controller\Parameters\Parameters;
use WebDevelovers\ResourceBundle\Controller\Parameters\RequestParameterProvider;
use WebDevelovers\ResourceBundle\Metadata\MetadataInterface;

use WebDevelovers\ResourceBundle\Routing\RouteFactory;
use function array_merge;
use function array_replace_recursive;
use function end;
use function explode;
use function is_array;
use function is_string;
use function sprintf;
use function str_starts_with;
use function str_contains;
use function substr;

readonly class RequestConfiguration
{
    private const string DEFAULT_TEMPLATES_NAMESPACE = '@WebDeveloversResource/crud';

    public function __construct(
        private(set) MetadataInterface $metadata,
        private(set) Request|null $request = null,
        private(set) Parameters $parameters = new Parameters([]),
    ) {
    }

    public function getSection(): string|null
    {
        $section = $this->parameters->get('section');

        return is_string($section) ? $section : null;
    }

    public function getRouteNamePrefix(): string|null
    {
        $prefix = $this->parameters->get('route_name_prefix');

        return is_string($prefix) ? $prefix : null;
    }

    public function getDefaultTemplate(string $name): string|null
    {
        $templatesNamespace = (string) ($this->metadata->templatesNamespace ?? self::DEFAULT_TEMPLATES_NAMESPACE);

        if (str_contains($templatesNamespace, ':')) {
            return sprintf('%s:%s.html.twig', $templatesNamespace, $name);
        }

        return sprintf('%s/%s.html.twig', $templatesNamespace, $name);
    }

    public function getTemplate(string $name): mixed
    {
        $template = $this->parameters->get('template', $this->getDefaultTemplate($name));
        if ($template === null) {
            throw new RuntimeException(sprintf(
                'Could not resolve template for resource "%s".',
                $this->metadata->getAlias(),
            ));
        }

        return $template;
    }

    public function getFormType(): string|null
    {
        $form = $this->parameters->get('form');
        if (isset($form['type'])) {
            return $form['type'];
        }

        if (is_string($form)) {
            return $form;
        }

        return $this->metadata->getClass('form');
    }

    /** @return array<string,mixed> */
    public function getFormOptions(): array
    {
        $form = $this->parameters->get('form');
        if (isset($form['options'])) {
            return $form['options'];
        }

        return [];
    }

    public function getInput(): string|null
    {
        $input = $this->parameters->get('input');

        if (! is_string($input)) {
            return null;
        }

        return $input;
    }

    public function getMessage(): string|null
    {
        $message = $this->parameters->get('message');

        if (! is_string($message)) {
            return null;
        }

        return $message;
    }

    public function getRouteName(string $name): string
    {
        return RouteFactory::generateRouteName(
            $this->metadata->applicationName,
            $this->metadata->name,
            $name,
            $this->getSection(),
            $this->getRouteNamePrefix(),
        );
    }

    /** @return array<string, string> */
    public function getRouteNames(): array
    {
        return [
            'index' => $this->getRouteName('index'),
            'create' => $this->getRouteName('create'),
            'show' => $this->getRouteName('show'),
            'update' => $this->getRouteName('update'),
            'delete' => $this->getRouteName('delete'),
        ];
    }

    public function getRedirectRoute(string $name, bool $allowRedirect = true): mixed
    {
        $redirect = $this->getRedirectConfiguration();
        if ($redirect === null || $allowRedirect === false) {
            return $this->getRouteName($name);
        }

        if (is_array($redirect)) {
            if (! empty($redirect['referer'])) {
                return 'referer';
            }

            return $redirect['route'] ?? $this->getRouteName($name);
        }

        return $redirect;
    }

    /**
     * Get url hash fragment (#text) which is you configured.
     */
    public function getRedirectHash(): string
    {
        $redirect = $this->getRedirectConfiguration();

        if (! is_array($redirect) || empty($redirect['hash'])) {
            return '';
        }

        return '#' . $redirect['hash'];
    }

    /**
     * Get redirect referer, This will detected by configuration
     * If not exists, The `referrer` from headers will be used.
     */
    public function getRedirectReferer(): string|null
    {
        $redirect = $this->getRedirectConfiguration();
        $referer = $this->getRequestReferer();

        if (! is_array($redirect) || empty($redirect['referer'])) {
            return $referer;
        }

        if ($redirect['referer'] === true) {
            return $referer;
        }

        return is_string($redirect['referer']) ? $redirect['referer'] : null;
    }

    /** @return array<string,mixed> */
    public function getRedirectParameters(object|null $resource = null): array
    {
        $redirect = $this->getRedirectConfiguration();

        if (isset($redirect['parameters']) && $redirect['parameters'] === []) {
            return [];
        }

        if (! is_array($redirect)) {
            $redirect = ['parameters' => []];
        }

        $parameters = $redirect['parameters'] ?? [];
        $parameters = $this->addExtraRedirectParameters($parameters);

        if ($resource !== null) {
            $parameters = $this->parseResourceValues($parameters, $resource);
        }

        return $parameters;
    }

    /**
     * @param array<string,mixed> $parameters
     *
     * @return array<string, mixed>
     */
    private function addExtraRedirectParameters(array $parameters): array
    {
        $vars = $this->getVars();
        $accessor = PropertyAccess::createPropertyAccessor();

        if ($accessor->isReadable($vars, '[redirect][parameters]')) {
            $extraParameters = $accessor->getValue($vars, '[redirect][parameters]');

            if (is_array($extraParameters)) {
                $parameters = array_merge($parameters, $extraParameters);
            }
        }

        return $parameters;
    }

    public function isLimited(): bool
    {
        return (bool) $this->parameters->get('limit', false);
    }

    public function getLimit(): int|null
    {
        $limit = null;

        if ($this->isLimited()) {
            $limit = (int) $this->parameters->get('limit', 10);
        }

        return $limit;
    }

    public function isPaginated(): bool
    {
        $pagination = $this->parameters->get('paginate', true);

        return $pagination !== false && $pagination !== null;
    }

    public function getPaginationMaxPerPage(): int
    {
        return (int) $this->parameters->get('paginate', 10);
    }

    public function isFilterable(): bool
    {
        return (bool) $this->parameters->get('filterable', false);
    }

    /**
     * @param array<string,mixed> $criteria
     *
     * @return array<string,mixed>
     */
    public function getCriteria(array $criteria = []): array
    {
        $defaultCriteria = array_merge($this->parameters->get('criteria', []), $criteria);

        if ($this->isFilterable()) {
            return $this->getRequestParameter('criteria', $defaultCriteria);
        }

        return $defaultCriteria;
    }

    public function isSortable(): bool
    {
        return (bool) $this->parameters->get('sortable', false);
    }

    /**
     * @param array<string,string> $sorting
     *
     * @return array<string,string>
     */
    public function getSorting(array $sorting = []): array
    {
        $defaultSorting = array_merge($this->parameters->get('sorting', []), $sorting);

        if ($this->isSortable()) {
            $sorting = $this->getRequestParameter('sorting');
            foreach ($defaultSorting as $key => $value) {
                if (isset($sorting[$key])) {
                    continue;
                }

                $sorting[$key] = $value;
            }

            return $sorting;
        }

        return $defaultSorting;
    }

    /**
     * @param array<string,mixed> $defaults
     *
     * @return array<string,mixed>
     */
    public function getRequestParameter(string $parameter, array $defaults = []): array
    {
        if ($this->request === null) {
            return $defaults;
        }

        return array_replace_recursive(
            $defaults,
            RequestParameterProvider::provide($this->request, $parameter, []),
        );
    }

    /** @return array<mixed>|string|null */
    public function getRepositoryMethod(): array|string|null
    {
        if (! $this->parameters->has('repository')) {
            return null;
        }

        $repository = $this->parameters->get('repository');

        if (! is_array($repository)) {
            return $repository;
        }

        return $repository['method'] ?? null;
    }

    /** @return array<string,mixed> */
    public function getRepositoryArguments(): array
    {
        if (! $this->parameters->has('repository')) {
            return [];
        }

        $repository = $this->parameters->get('repository');

        if (! isset($repository['arguments'])) {
            return [];
        }

        return is_array($repository['arguments']) ? $repository['arguments'] : [$repository['arguments']];
    }

    public function getFlashMessage(string $message): mixed
    {
        return $this->parameters->get('flash', sprintf(
            '%s.%s.%s',
            $this->metadata->applicationName,
            $this->metadata->name,
            $message,
        ));
    }

    public function getSortablePosition(): mixed
    {
        return $this->parameters->get('sortable_position', 'position');
    }

    public function getEvent(): string|null
    {
        return $this->parameters->get('event');
    }

    public function hasPermission(): bool
    {
        return $this->parameters->get('permission', false) !== false;
    }

    public function getPermission(string $name): string|null
    {
        $permission = $this->parameters->get('permission');

        if ($permission === true) {
            return $this->formatPermission($name);
        }

        return $permission;
    }

    public function isHeaderRedirection(): bool
    {
        $redirect = $this->getRedirectConfiguration();

        if (! is_array($redirect) || ! isset($redirect['header'])) {
            return false;
        }

        if ($this->request === null) {
            return false;
        }

        if ($redirect['header'] === 'xhr') {
            return $this->request->isXmlHttpRequest();
        }

        return (bool) $redirect['header'];
    }

    /** @return array<string,mixed> */
    public function getVars(): array
    {
        return $this->parameters->get('vars', []);
    }

    /**
     * @param array<string,mixed> $parameters
     *
     * @return array<string,mixed>
     */
    private function parseResourceValues(array $parameters, object $resource): array
    {
        $accessor = PropertyAccess::createPropertyAccessor();

        if (empty($parameters)) {
            return ['id' => $accessor->getValue($resource, 'id')];
        }

        foreach ($parameters as $key => $value) {
            if (is_array($value)) {
                $parameters[$key] = $this->parseResourceValues($value, $resource);

                continue;
            }

            if (! is_string($value) || ! str_starts_with($value, 'resource.')) {
                continue;
            }

            $parameters[$key] = $accessor->getValue($resource, substr($value, 9));
        }

        return $parameters;
    }

    private function getRedirectConfiguration(): mixed
    {
        return $this->parameters->get('redirect');
    }

    private function getRequestReferer(): string|null
    {
        if ($this->request === null) {
            return null;
        }

        $referer = $this->request->headers->get('referer');

        return is_string($referer) ? $referer : null;
    }

    private function formatPermission(string $name): string
    {
        return sprintf('%s.%s.%s', $this->metadata->applicationName, $this->metadata->name, $name);
    }

    public function hasIndex(): bool
    {
        return $this->parameters->has('index');
    }

    public function getIndex(): string|null
    {
        return $this->parameters->get('index');
    }

    public function hasStateMachine(): bool
    {
        return $this->parameters->has('state_machine');
    }

    public function getStateMachineGraph(): string|null
    {
        $options = $this->parameters->get('state_machine');

        return $options['graph'] ?? null;
    }

    public function getStateMachineTransition(): string|null
    {
        $options = $this->parameters->get('state_machine');

        return $options['transition'] ?? null;
    }

    public function isCsrfProtectionEnabled(): bool
    {
        return $this->parameters->get('csrf_protection', true);
    }

    /** @return array<string,mixed> */
    public function getIndexVars(): array
    {
        $vars = $this->getVars();
        $indexVars = $vars['index'] ?? [];

        return is_array($indexVars) ? $indexVars : [];
    }

    public function getIndexTitle(): string
    {
        $indexVars = $this->getIndexVars();
        $title = $indexVars['title'] ?? null;

        if (is_string($title) && $title !== '') {
            return $title;
        }

        if ($this->getRouteNamePrefix() === null) {
            $parts = explode('.', $this->metadata->getAlias());
            $resourceName = end($parts);
        } else {
            $resourceName = $this->getRouteNamePrefix();
        }

        return sprintf('wd.resource.%s.plural', $resourceName);
    }

    /** @return array<string, mixed> */
    public function getIndexComponentContext(string $defaultView, string $template): array
    {
        return [
            'index' => $this->getIndex() ?? $this->metadata->getAlias(),
            'resourceAlias' => $this->metadata->getAlias(),
            'section' => $this->getSection(),
            'routeNamePrefix' => $this->getRouteNamePrefix(),
            'vars' => $this->getIndexVars(),
            'title' => $this->getIndexTitle(),
            'routes' => $this->getRouteNames(),
            'defaultView' => $defaultView,
            'template' => $template,
        ];
    }
}
