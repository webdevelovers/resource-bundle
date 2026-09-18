<?php

declare(strict_types=1);

namespace WebDevelovers\ResourceBundle\Tests\Controller\Parameters;

use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use WebDevelovers\ResourceBundle\Controller\Parameters\ParametersParser;

final class ParametersParserTest extends TestCase
{
    public function testParseRequestValuesParsesNestedDynamicValues(): void
    {
        $request = new Request(['count' => '12', 'enabled' => '1']);
        $request->attributes->set('foo', 'attr-value');

        $parser = new ParametersParser();

        $result = $parser->parseRequestValues([
            'fromRequest' => '$foo',
            'count' => '!!int $count',
            'flags' => ['enabled' => '!!bool $enabled'],
            'raw' => 42,
        ], $request);

        self::assertSame('attr-value', $result['fromRequest']);
        self::assertSame(12, $result['count']);
        self::assertTrue($result['flags']['enabled']);
        self::assertSame(42, $result['raw']);
    }

    public function testParseRequestValuesLeavesPlainStringsUntouched(): void
    {
        $request = new Request();
        $parser = new ParametersParser();

        $result = $parser->parseRequestValues(['value' => 'plain-string'], $request);

        self::assertSame('plain-string', $result['value']);
    }
}
