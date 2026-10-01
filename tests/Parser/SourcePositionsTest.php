<?php

declare(strict_types=1);

namespace SmartyAst\Tests\Parser;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use SmartyAst\Ast\Node;
use SmartyAst\Ast\VariableExpressionNode;
use SmartyAst\ParseOptions;
use SmartyAst\Parser\SmartyParser;

final class SourcePositionsTest extends TestCase
{
    #[DataProvider('templates')]
    public function testVariablesSelectTheirExactSource(string $source, ?ParseOptions $options = null): void
    {
        $result = (new SmartyParser())->parseString($source, $options);
        self::assertSame([], $result->diagnostics);
        $variables = [];
        $walk = function (Node $node) use (&$walk, &$variables, $source): void {
            if ($node instanceof VariableExpressionNode) {
                $variables[] = $node;
                $start = $node->span->start;
                $end = $node->span->end;
                self::assertSame('$' . $node->name, substr($source, $start->offset, $end->offset - $start->offset));
                $prefix = substr($source, 0, $start->offset);
                self::assertSame(1 + substr_count($prefix, "\n"), $start->line);
                $newline = strrpos($prefix, "\n");
                self::assertSame($newline === false ? $start->offset + 1 : $start->offset - $newline, $start->column);
            }
            foreach ($node->children() as $child) {
                $walk($child);
            }
        };
        $walk($result->ast);
        self::assertNotEmpty($variables);
    }

    public static function templates(): iterable
    {
        yield ['{$foo}'];
        yield ["{include file='a.tpl' title=\$title}"];
        yield ["{if\n  \$foo}yes{elseif\n \$bar}no{/if}"];
        yield ["{-\n \$foo|replace:\$old:\$new -}"];
        yield ['[[- $foo -]]', new ParseOptions(leftDelimiter: '[[', rightDelimiter: ']]')];
        yield ['{foreach $items as $key => $item}{$item}{/foreach}'];
        yield ["{func value=\"hello\n\$name and `\$other.foo` {if \$ok}yes{/if}\"}"];
    }

    public function testConfigShorthandSpansStayInsideTheSource(): void
    {
        $source = '{#foo#}';
        $result = (new SmartyParser())->parseString($source);
        self::assertSame([], $result->diagnostics);
        $expression = $result->ast->children[0]->expression;
        self::assertSame('foo', $expression->property);
        self::assertSame(1, $expression->span->start->offset);
        self::assertSame(6, $expression->span->end->offset);
        self::assertSame($expression->target->span->start, $expression->target->span->end);
    }
}
