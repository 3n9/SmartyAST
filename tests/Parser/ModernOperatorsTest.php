<?php

declare(strict_types=1);

namespace SmartyAst\Tests\Parser;

use SmartyAst\Ast\BinaryExpressionNode;
use SmartyAst\Ast\TernaryExpressionNode;
use SmartyAst\Parser\SmartyParser;
use PHPUnit\Framework\TestCase;

final class ModernOperatorsTest extends TestCase
{
    private SmartyParser $parser;

    protected function setUp(): void
    {
        $this->parser = new SmartyParser();
    }

    public function testFullTernaryExpressionParses(): void
    {
        $expr = $this->parsePrintExpression('{$test ? "OK" : "FAIL"}');
        $this->assertInstanceOf(TernaryExpressionNode::class, $expr);
    }

    public function testShorthandTernaryElvisParses(): void
    {
        $expr = $this->parsePrintExpression('{$myVar ?: "empty"}');
        $this->assertInstanceOf(TernaryExpressionNode::class, $expr);
    }

    public function testNullCoalesceParsesAsBinaryOperator(): void
    {
        $expr = $this->parsePrintExpression('{$myVar ?? "empty"}');
        $this->assertInstanceOf(BinaryExpressionNode::class, $expr);
        $this->assertSame('??', $expr->operator);
    }

    public function testProvidedSequenceParsesWithoutErrors(): void
    {
        $template = <<<'TPL'
{$myVar ?: "empty"}
{$myVar="hello"}
{$myVar ?: "empty"}
{$myVar ?? "empty"}
{$myVar=""}
{$myVar ?: "this is not shown"}
TPL;
        $result = $this->parser->parseString($template);
        $errors = array_filter($result->diagnostics, static fn ($d) => $d->severity->value === 'error');
        $this->assertCount(0, $errors);
    }

    private function parsePrintExpression(string $template)
    {
        $result = $this->parser->parseString($template);
        $errors = array_filter($result->diagnostics, static fn ($d) => $d->severity->value === 'error');
        $this->assertCount(0, $errors);

        $print = $result->ast->children[0] ?? null;
        $this->assertNotNull($print);
        return $print->expression;
    }
}
