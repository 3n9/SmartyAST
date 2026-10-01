<?php

declare(strict_types=1);

namespace SmartyAst\Tests\Parser;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use SmartyAst\ParseException;
use SmartyAst\ParseOptions;
use SmartyAst\Parser\SmartyParser;

final class InvalidInputTest extends TestCase
{
    #[DataProvider('invalidTemplates')]
    public function testInvalidInputProducesDiagnostics(string $source, string $code): void
    {
        $result = (new SmartyParser())->parseString($source);
        self::assertContains($code, array_column($result->diagnostics, 'code'));
    }

    #[DataProvider('invalidTemplates')]
    public function testDisablingRecoveryThrowsWithTheDiagnostic(string $source, string $code): void
    {
        try {
            (new SmartyParser())->parseString($source, new ParseOptions(recoverErrors: false));
            self::fail('Expected a parse exception');
        } catch (ParseException $e) {
            self::assertSame($code, $e->diagnostic->code);
        }
    }

    public static function invalidTemplates(): iterable
    {
        yield ['{count($items}', 'EXPR019'];
        yield ['{$foo~}', 'EXPR001'];
        yield ['{include file=}', 'EXPR018'];
        yield ['{if $foo}', 'PARSE001'];
        yield ['{* missing close', 'LEX001'];
    }

    public function testStrictModeAcceptsValidInput(): void
    {
        self::assertSame([], (new SmartyParser())->parseString('{count($items)}', new ParseOptions(recoverErrors: false))->diagnostics);
    }

    public function testEmptyDelimitersAreRejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new ParseOptions(leftDelimiter: '');
    }
}
