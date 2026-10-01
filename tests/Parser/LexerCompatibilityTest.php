<?php

declare(strict_types=1);

namespace SmartyAst\Tests\Parser;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use SmartyAst\Ast\CommentNode;
use SmartyAst\Ast\PrintNode;
use SmartyAst\Ast\TextNode;
use SmartyAst\ParseOptions;
use SmartyAst\Parser\SmartyParser;

final class LexerCompatibilityTest extends TestCase
{
    #[DataProvider('rawBlocks')]
    public function testRawContentIsNeverTokenized(string $name, string $content): void
    {
        $result = (new SmartyParser())->parseString('{' . $name . '}' . $content . '{/' . $name . '}{$after}');
        self::assertSame([], $result->diagnostics);
        self::assertSame($content, $result->ast->children[0]->children[0]->text);
        self::assertInstanceOf(PrintNode::class, $result->ast->children[1]);
    }

    public static function rawBlocks(): iterable
    {
        yield ['literal', 'var x = "{";'];
        yield ['literal', '{* unterminated comment and "quote'];
        yield ['literal', '{/php} is plain text'];
        yield ['php', 'echo "{";'];
    }

    public function testCustomDelimitersHandleCommentsAndRawBlocks(): void
    {
        $result = (new SmartyParser())->parseString('[[* hello *]][[literal]][[ " [[/literal]][[$x]]', new ParseOptions(leftDelimiter: '[[', rightDelimiter: ']]'));
        self::assertSame([], $result->diagnostics);
        self::assertInstanceOf(CommentNode::class, $result->ast->children[0]);
        self::assertSame(' hello ', $result->ast->children[0]->text);
        self::assertSame('[[ " ', $result->ast->children[1]->children[0]->text);
        self::assertInstanceOf(PrintNode::class, $result->ast->children[2]);
    }

    public function testWhitespaceDelimitersAreLiteralByDefault(): void
    {
        $source = "<script>function f() {\n return 1;\n}</script>";
        $result = (new SmartyParser())->parseString($source);
        self::assertSame([], $result->diagnostics);
        foreach ($result->ast->children as $child) {
            self::assertInstanceOf(TextNode::class, $child);
        }
        self::assertSame($source, implode('', array_column($result->ast->children, 'text')));
        $result = (new SmartyParser())->parseString('{ $x }', new ParseOptions(autoLiteral: false));
        self::assertInstanceOf(PrintNode::class, $result->ast->children[0]);
    }

    public function testStaticCallsAreExpressions(): void
    {
        $result = (new SmartyParser())->parseString('{Cls::method()}');
        self::assertSame([], $result->diagnostics);
        self::assertInstanceOf(PrintNode::class, $result->ast->children[0]);
    }

    public function testUnclosedBlocksKeepTheirNesting(): void
    {
        $result = (new SmartyParser())->parseString('{if $a}{foreach $items as $item}text');
        self::assertCount(2, $result->diagnostics);
        self::assertCount(1, $result->ast->children);
        self::assertSame('foreach', $result->ast->children[0]->children[0]->openTag->name);
    }
}
