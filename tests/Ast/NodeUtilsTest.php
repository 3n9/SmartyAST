<?php

declare(strict_types=1);

namespace SmartyAst\Tests\Ast;

use PHPUnit\Framework\TestCase;
use SmartyAst\Parser\SmartyParser;
use SmartyAst\Ast\BinaryExpressionNode;
use SmartyAst\Ast\LiteralExpressionNode;
use SmartyAst\Ast\PrintNode;
use SmartyAst\Ast\TagNode;
use SmartyAst\Ast\VariableExpressionNode;

/**
 * Tests for utility methods added to AST node classes:
 *   - LiteralExpressionNode::asString / asInt / asFloat / asBool
 *   - TagNode::findArgument
 *   - ExpressionNode::collectVariableNames
 *   - ExpressionNode::countBinaryOperands
 */
final class NodeUtilsTest extends TestCase
{
    private SmartyParser $parser;

    protected function setUp(): void
    {
        $this->parser = new SmartyParser();
    }

    // -----------------------------------------------------------------------
    // LiteralExpressionNode typed accessors
    // -----------------------------------------------------------------------

    public function testAsStringReturnStringLiteral(): void
    {
        $result = $this->parser->parseString('{assign var="x" value="hello"}');
        $tag    = $result->ast->children()[0];
        $this->assertInstanceOf(TagNode::class, $tag);

        $valueArg = $tag->findArgument('value');
        $this->assertNotNull($valueArg);
        $this->assertInstanceOf(LiteralExpressionNode::class, $valueArg->value);
        $this->assertSame('hello', $valueArg->value->asString());
        $this->assertNull($valueArg->value->asInt());
        $this->assertNull($valueArg->value->asBool());
    }

    public function testAsIntReturnIntLiteral(): void
    {
        $result = $this->parser->parseString('{assign var="x" value=42}');
        $tag    = $result->ast->children()[0];
        $this->assertInstanceOf(TagNode::class, $tag);

        $valueArg = $tag->findArgument('value');
        $this->assertNotNull($valueArg);
        $this->assertInstanceOf(LiteralExpressionNode::class, $valueArg->value);
        $this->assertSame(42, $valueArg->value->asInt());
        $this->assertNull($valueArg->value->asString());
        $this->assertNull($valueArg->value->asBool());
    }

    public function testAsBoolReturnTrueLiteral(): void
    {
        $result = $this->parser->parseString('{assign var="x" value=true}');
        $tag    = $result->ast->children()[0];
        $this->assertInstanceOf(TagNode::class, $tag);

        $valueArg = $tag->findArgument('value');
        $this->assertNotNull($valueArg);
        $this->assertInstanceOf(LiteralExpressionNode::class, $valueArg->value);
        $this->assertTrue($valueArg->value->asBool());
        $this->assertNull($valueArg->value->asString());
    }

    // -----------------------------------------------------------------------
    // TagNode::findArgument
    // -----------------------------------------------------------------------

    public function testFindArgumentByName(): void
    {
        $result = $this->parser->parseString('{include file="a.tpl"}');
        $tag    = $result->ast->children()[0];
        $this->assertInstanceOf(TagNode::class, $tag);

        $arg = $tag->findArgument('file');
        $this->assertNotNull($arg);
        $this->assertSame('file', $arg->name);
    }

    public function testFindArgumentByNameCaseInsensitive(): void
    {
        $result = $this->parser->parseString('{include file="a.tpl"}');
        $tag    = $result->ast->children()[0];
        $this->assertInstanceOf(TagNode::class, $tag);

        $this->assertNotNull($tag->findArgument('FILE'));
        $this->assertNotNull($tag->findArgument('File'));
    }

    public function testFindArgumentReturnsNullWhenNotFound(): void
    {
        $result = $this->parser->parseString('{include file="a.tpl"}');
        $tag    = $result->ast->children()[0];
        $this->assertInstanceOf(TagNode::class, $tag);

        $this->assertNull($tag->findArgument('nonexistent'));
    }

    public function testFindArgumentShorthandUsesFirstPositional(): void
    {
        // Shorthand: {include "a.tpl"} uses isShorthand=true with a positional first arg.
        $result = $this->parser->parseString('{include "a.tpl"}');
        $tag    = $result->ast->children()[0];
        $this->assertInstanceOf(TagNode::class, $tag);
        $this->assertTrue($tag->isShorthand);

        // findArgument('file') should return the first positional arg as fallback.
        $arg = $tag->findArgument('file');
        $this->assertNotNull($arg);
    }

    // -----------------------------------------------------------------------
    // ExpressionNode::collectVariableNames
    // -----------------------------------------------------------------------

    public function testCollectVariableNamesSimpleVariable(): void
    {
        $result = $this->parser->parseString('{$foo}');
        $print  = $result->ast->children()[0];
        $this->assertInstanceOf(PrintNode::class, $print);

        $names = $print->expression->collectVariableNames();
        $this->assertSame(['foo'], $names);
    }

    public function testCollectVariableNamesFromBinaryExpression(): void
    {
        $result = $this->parser->parseString('{$a + $b}');
        $print  = $result->ast->children()[0];
        $this->assertInstanceOf(PrintNode::class, $print);

        $names = $print->expression->collectVariableNames();
        sort($names);
        $this->assertSame(['a', 'b'], $names);
    }

    public function testCollectVariableNamesDeduplicates(): void
    {
        $result = $this->parser->parseString('{$a + $a}');
        $print  = $result->ast->children()[0];
        $this->assertInstanceOf(PrintNode::class, $print);

        $names = $print->expression->collectVariableNames();
        $this->assertSame(['a'], $names);
    }

    public function testCollectVariableNamesLiteralReturnsEmpty(): void
    {
        $result = $this->parser->parseString('{"hello"}');
        $print  = $result->ast->children()[0];
        $this->assertInstanceOf(PrintNode::class, $print);

        $this->assertSame([], $print->expression->collectVariableNames());
    }

    // -----------------------------------------------------------------------
    // ExpressionNode::countBinaryOperands
    // -----------------------------------------------------------------------

    public function testCountBinaryOperandsSimpleVariable(): void
    {
        $result = $this->parser->parseString('{$a}');
        $print  = $result->ast->children()[0];
        $this->assertInstanceOf(PrintNode::class, $print);
        $this->assertInstanceOf(VariableExpressionNode::class, $print->expression);

        $this->assertSame(1, $print->expression->countBinaryOperands());
    }

    public function testCountBinaryOperandsTwoOperands(): void
    {
        $result = $this->parser->parseString('{$a && $b}');
        $print  = $result->ast->children()[0];
        $this->assertInstanceOf(PrintNode::class, $print);
        $this->assertInstanceOf(BinaryExpressionNode::class, $print->expression);

        $this->assertSame(2, $print->expression->countBinaryOperands());
    }

    public function testCountBinaryOperandsThreeOperands(): void
    {
        $result = $this->parser->parseString('{$a && $b || $c}');
        $print  = $result->ast->children()[0];
        $this->assertInstanceOf(PrintNode::class, $print);

        $this->assertSame(3, $print->expression->countBinaryOperands());
    }

    public function testCountBinaryOperandsUnwrapsParentheses(): void
    {
        $result = $this->parser->parseString('{$a && ($b || $c)}');
        $print  = $result->ast->children()[0];
        $this->assertInstanceOf(PrintNode::class, $print);

        // ($b || $c) is a UnaryExpressionNode wrapping a BinaryExpressionNode; total is still 3.
        $this->assertSame(3, $print->expression->countBinaryOperands());
    }
}
