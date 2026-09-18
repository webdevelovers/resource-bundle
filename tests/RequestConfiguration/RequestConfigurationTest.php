<?php

declare(strict_types=1);

namespace WebDevelovers\ResourceBundle\Tests\RequestConfiguration;

use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use WebDevelovers\ResourceBundle\Controller\Parameters\Parameters;
use WebDevelovers\ResourceBundle\Metadata\Metadata;
use WebDevelovers\ResourceBundle\RequestConfiguration\RequestConfiguration;

final class RequestConfigurationTest extends TestCase
{
    public function testGetSection(): void
    {
        $metadata = Metadata::fromAliasAndConfiguration('app.product', []);
        $config = new RequestConfiguration($metadata, null, new Parameters(['section' => 'admin']));

        self::assertSame('admin', $config->getSection());

        $configNone = new RequestConfiguration($metadata, null, new Parameters([]));
        self::assertNull($configNone->getSection());
    }

    public function testGetRouteNamePrefix(): void
    {
        $metadata = Metadata::fromAliasAndConfiguration('app.product', []);
        $config = new RequestConfiguration($metadata, null, new Parameters(['route_name_prefix' => 'web']));

        self::assertSame('web', $config->getRouteNamePrefix());
    }

    public function testGetDefaultTemplate(): void
    {
        $metadata = Metadata::fromAliasAndConfiguration('app.product', [
            'templates' => 'admin/product',
        ]);
        $config = new RequestConfiguration($metadata);

        self::assertSame('admin/product/index.html.twig', $config->getDefaultTemplate('index'));

        $metadataDefault = Metadata::fromAliasAndConfiguration('app.product', []);
        $configDefault = new RequestConfiguration($metadataDefault);
        self::assertSame('@WebDeveloversResource/crud/index.html.twig', $configDefault->getDefaultTemplate('index'));

        $metadataColon = Metadata::fromAliasAndConfiguration('app.product', [
            'templates' => 'App:Product',
        ]);
        $configColon = new RequestConfiguration($metadataColon);
        self::assertSame('App:Product:index.html.twig', $configColon->getDefaultTemplate('index'));
    }

    public function testGetTemplate(): void
    {
        $metadata = Metadata::fromAliasAndConfiguration('app.product', [
            'templates' => 'admin/product',
        ]);
        $config = new RequestConfiguration($metadata, null, new Parameters(['template' => 'custom.twig']));

        self::assertSame('custom.twig', $config->getTemplate('index'));

        $configDefault = new RequestConfiguration($metadata);
        self::assertSame('admin/product/index.html.twig', $configDefault->getTemplate('index'));
    }

    public function testGetTemplateUsesBundleDefaultWhenTemplatesNamespaceIsMissing(): void
    {
        $metadata = Metadata::fromAliasAndConfiguration('app.product', []);
        $config = new RequestConfiguration($metadata);

        self::assertSame('@WebDeveloversResource/crud/index.html.twig', $config->getTemplate('index'));
    }

    public function testGetFormTypeAndOptions(): void
    {
        $metadata = Metadata::fromAliasAndConfiguration('app.product', [
            'classes' => ['form' => 'App\Form\ProductType'],
        ]);

        $config = new RequestConfiguration($metadata, null, new Parameters([
            'form' => [
                'type' => 'CustomType',
                'options' => ['foo' => 'bar'],
            ],
        ]));

        self::assertSame('CustomType', $config->getFormType());
        self::assertSame(['foo' => 'bar'], $config->getFormOptions());

        $configString = new RequestConfiguration($metadata, null, new Parameters(['form' => 'StringType']));
        self::assertSame('StringType', $configString->getFormType());
        self::assertSame([], $configString->getFormOptions());

        $configMetadata = new RequestConfiguration($metadata);
        self::assertSame('App\Form\ProductType', $configMetadata->getFormType());
    }

    public function testGetRedirectRoute(): void
    {
        $metadata = Metadata::fromAliasAndConfiguration('app.product', [
            'application_name' => 'app',
            'name' => 'product',
        ]);

        $config = new RequestConfiguration($metadata, null, new Parameters(['redirect' => 'my_custom_route']));
        self::assertSame('my_custom_route', $config->getRedirectRoute('index'));

        $configDefault = new RequestConfiguration($metadata);
        self::assertSame('app_product_index', $configDefault->getRedirectRoute('index'));

        $configReferer = new RequestConfiguration($metadata, null, new Parameters(['redirect' => ['referer' => true]]));
        self::assertSame('referer', $configReferer->getRedirectRoute('index'));

        $configWithoutRoute = new RequestConfiguration($metadata, null, new Parameters(['redirect' => ['hash' => 'foo']]));
        self::assertSame('app_product_index', $configWithoutRoute->getRedirectRoute('index'));
    }

    public function testGetRedirectReferer(): void
    {
        $metadata = Metadata::fromAliasAndConfiguration('app.product', []);
        $request = new Request();
        $request->headers->set('referer', 'http://google.com');

        $config = new RequestConfiguration($metadata, $request);
        self::assertSame('http://google.com', $config->getRedirectReferer());

        $configManual = new RequestConfiguration($metadata, $request, new Parameters(['redirect' => ['referer' => 'http://manual.com']]));
        self::assertSame('http://manual.com', $configManual->getRedirectReferer());
    }

    public function testGetRedirectParameters(): void
    {
        $metadata = Metadata::fromAliasAndConfiguration('app.product', []);
        $config = new RequestConfiguration($metadata, null, new Parameters([
            'redirect' => [
                'parameters' => ['foo' => 'bar', 'id' => 'resource.id'],
            ],
        ]));

        $resource = new \stdClass();
        $resource->id = 123;

        $params = $config->getRedirectParameters($resource);
        self::assertSame('bar', $params['foo']);
        self::assertSame(123, $params['id']);

        $configDefault = new RequestConfiguration($metadata, null, new Parameters([]));
        self::assertSame(['id' => 123], $configDefault->getRedirectParameters($resource));

        $configVars = new RequestConfiguration($metadata, null, new Parameters([
            'vars' => ['redirect' => ['parameters' => ['baz' => 'qux']]],
        ]));
        self::assertSame(['baz' => 'qux'], $configVars->getRedirectParameters());
    }

    public function testGetRepositoryConfiguration(): void
    {
        $metadata = Metadata::fromAliasAndConfiguration('app.product', []);

        $configMethodString = new RequestConfiguration($metadata, null, new Parameters(['repository' => 'findAll']));
        self::assertSame('findAll', $configMethodString->getRepositoryMethod());
        self::assertSame([], $configMethodString->getRepositoryArguments());

        $configMethodArray = new RequestConfiguration($metadata, null, new Parameters([
            'repository' => [
                'method' => 'findByStatus',
                'arguments' => ['enabled' => true],
            ],
        ]));
        self::assertSame('findByStatus', $configMethodArray->getRepositoryMethod());
        self::assertSame(['enabled' => true], $configMethodArray->getRepositoryArguments());

        $configWithoutMethod = new RequestConfiguration($metadata, null, new Parameters(['repository' => ['arguments' => [1]]]));
        self::assertNull($configWithoutMethod->getRepositoryMethod());
    }

    public function testIsHeaderRedirection(): void
    {
        $metadata = Metadata::fromAliasAndConfiguration('app.product', []);
        $request = new Request();

        $configWithoutRequest = new RequestConfiguration($metadata, null, new Parameters(['redirect' => ['header' => true]]));
        self::assertFalse($configWithoutRequest->isHeaderRedirection());

        $configTrue = new RequestConfiguration($metadata, $request, new Parameters(['redirect' => ['header' => true]]));
        self::assertTrue($configTrue->isHeaderRedirection());

        $xhrRequest = new Request(server: ['HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest']);
        $configXhr = new RequestConfiguration($metadata, $xhrRequest, new Parameters(['redirect' => ['header' => 'xhr']]));
        self::assertTrue($configXhr->isHeaderRedirection());
    }

    public function testStateMachineAndCsrf(): void
    {
        $metadata = Metadata::fromAliasAndConfiguration('app.product', []);
        $config = new RequestConfiguration($metadata, null, new Parameters([
            'state_machine' => ['graph' => 'order', 'transition' => 'complete'],
            'csrf_protection' => false,
        ]));

        self::assertTrue($config->hasStateMachine());
        self::assertSame('order', $config->getStateMachineGraph());
        self::assertSame('complete', $config->getStateMachineTransition());
        self::assertFalse($config->isCsrfProtectionEnabled());
    }

    public function testGetCriteria(): void
    {
        $metadata = Metadata::fromAliasAndConfiguration('app.product', []);
        $config = new RequestConfiguration($metadata, null, new Parameters([
            'criteria' => ['enabled' => true],
            'filterable' => true,
        ]));

        // Senza request parameters
        self::assertSame(['enabled' => true], $config->getCriteria());

        // Con request (mocking RequestParameterProvider è difficile perché è una classe con metodo statico)
        // In questo ambiente non posso mockare facilmente i parametri del request che verrebbero letti da RequestParameterProvider::provide
        // ma posso testare almeno la parte dei default
    }

    public function testIsPaginated(): void
    {
        $metadata = Metadata::fromAliasAndConfiguration('app.product', []);

        $config = new RequestConfiguration($metadata, null, new Parameters(['paginate' => false]));
        self::assertFalse($config->isPaginated());

        $configTrue = new RequestConfiguration($metadata, null, new Parameters(['paginate' => 20]));
        self::assertTrue($configTrue->isPaginated());
        self::assertSame(20, $configTrue->getPaginationMaxPerPage());
    }

    public function testGetPermission(): void
    {
        $metadata = Metadata::fromAliasAndConfiguration('app.product', [
            'application_name' => 'app',
            'name' => 'product',
        ]);

        $config = new RequestConfiguration($metadata, null, new Parameters(['permission' => true]));
        self::assertSame('app.product.index', $config->getPermission('index'));

        $configString = new RequestConfiguration($metadata, null, new Parameters(['permission' => 'custom_perm']));
        self::assertSame('custom_perm', $configString->getPermission('index'));
    }

    public function testGetIndexTitle(): void
    {
        $metadata = Metadata::fromAliasAndConfiguration('app.product', [
            'application_name' => 'app',
            'name' => 'product',
        ]);

        $config = new RequestConfiguration($metadata);
        self::assertSame('wd.resource.product.plural', $config->getIndexTitle());

        $configPrefix = new RequestConfiguration($metadata, null, new Parameters(['route_name_prefix' => 'web']));
        self::assertSame('wd.resource.web.plural', $configPrefix->getIndexTitle());

        $configCustom = new RequestConfiguration($metadata, null, new Parameters(['vars' => ['index' => ['title' => 'Custom Title']]]));
        self::assertSame('Custom Title', $configCustom->getIndexTitle());
    }
}
