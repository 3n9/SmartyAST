<?php

declare(strict_types=1);

namespace SmartyAst\Tests\Parser;

use SmartyAst\Ast\ArrayExpressionNode;
use SmartyAst\Ast\BinaryExpressionNode;
use SmartyAst\Ast\TagNode;
use SmartyAst\Parser\SmartyParser;
use PHPUnit\Framework\TestCase;

final class MultilineAssignmentTest extends TestCase
{
    private SmartyParser $parser;

    protected function setUp(): void
    {
        $this->parser = new SmartyParser();
    }

    public function testParsesMultilineAssignArrayLiteral(): void
    {
        $template = <<<'TPL'
{assign var="array" value=[
  1,
  2,
  3
]}
TPL;

        $result = $this->parser->parseString($template);
        $errors = array_filter($result->diagnostics, static fn ($d) => $d->severity->value === 'error');
        $this->assertCount(0, $errors);

        $assign = null;
        foreach ($result->ast->children as $node) {
            if ($node instanceof TagNode && $node->name === 'assign') {
                $assign = $node;
                break;
            }
        }

        $this->assertNotNull($assign);
        $valueArg = null;
        foreach ($assign->arguments as $arg) {
            if ($arg->name === 'value') {
                $valueArg = $arg;
                break;
            }
        }

        $this->assertNotNull($valueArg);
        $this->assertInstanceOf(ArrayExpressionNode::class, $valueArg->value);
    }

    public function testParsesMultilinePrintAssignmentArrayLiteral(): void
    {
        $template = <<<'TPL'
{$array = [
   'key1' => 'value1',
   'key2' => 'value2',
]}
TPL;

        $result = $this->parser->parseString($template);
        $errors = array_filter($result->diagnostics, static fn ($d) => $d->severity->value === 'error');
        $this->assertCount(0, $errors);

        $print = $result->ast->children[0] ?? null;
        $this->assertNotNull($print);
        $this->assertInstanceOf(BinaryExpressionNode::class, $print->expression);
        $this->assertSame('=', $print->expression->operator);
        $this->assertInstanceOf(ArrayExpressionNode::class, $print->expression->right);
    }

    public function testReportsErrorForMissingClosingBracketInAssignArray(): void
    {
        $template = <<<'TPL'
{assign var="array" value=[
  1,
  2,
  3
}
TPL;

        $result = $this->parser->parseString($template);
        $codes = array_map(static fn ($d) => $d->code, array_filter($result->diagnostics, static fn ($d) => $d->severity->value === 'error'));

        $this->assertContains('EXPR012', $codes);
    }

    public function testReportsErrorForMissingClosingBracketInPrintAssignmentArray(): void
    {
        $template = <<<'TPL'
{$array = [
   'key1' => 'value1',
   'key2' => 'value2',
}
TPL;

        $result = $this->parser->parseString($template);
        $codes = array_map(static fn ($d) => $d->code, array_filter($result->diagnostics, static fn ($d) => $d->severity->value === 'error'));

        $this->assertContains('EXPR012', $codes);
    }
}
