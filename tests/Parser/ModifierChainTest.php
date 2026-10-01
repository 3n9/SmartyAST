<?php

declare(strict_types=1);

namespace SmartyAst\Tests\Parser;

use PHPUnit\Framework\TestCase;
use SmartyAst\Ast\ModifierChainExpressionNode;
use SmartyAst\Parser\SmartyParser;

final class ModifierChainTest extends TestCase
{
    public function testArgumentsDoNotConsumeFollowingModifiers(): void
    {
        $result = (new SmartyParser())->parseString('{"abc"|replace:"a":"b"|upper|truncate:2:"!"|escape}');
        self::assertSame([], $result->diagnostics);
        $chain = $result->ast->children[0]->expression;
        self::assertSame(['replace', 'upper', 'truncate', 'escape'], array_column($chain->modifiers, 'name'));
        self::assertSame(['a', 'b'], array_column($chain->modifiers[0]->arguments, 'value'));
        self::assertSame([2, '!'], array_column($chain->modifiers[2]->arguments, 'value'));
    }

    public function testGroupedArgumentsRetainTheirOwnChains(): void
    {
        $result = (new SmartyParser())->parseString('{$text|replace:($needle|lower):$replacement|upper}');
        self::assertSame([], $result->diagnostics);
        $chain = $result->ast->children[0]->expression;
        self::assertSame(['replace', 'upper'], array_column($chain->modifiers, 'name'));
        $group = $chain->modifiers[0]->arguments[0];
        self::assertInstanceOf(ModifierChainExpressionNode::class, $group->expression);
        self::assertSame('lower', $group->expression->modifiers[0]->name);
    }

    public function testNegativeArgumentDoesNotConsumeFollowingModifier(): void
    {
        $result = (new SmartyParser())->parseString('{$text|truncate:-1|escape}');
        self::assertSame([], $result->diagnostics);
        self::assertSame(['truncate', 'escape'], array_column($result->ast->children[0]->expression->modifiers, 'name'));
    }
}
