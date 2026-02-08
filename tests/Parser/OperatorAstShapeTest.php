<?php

declare(strict_types=1);

namespace Dev\Smarty\Tests\Parser;

use Dev\Smarty\Ast\BinaryExpressionNode;
use Dev\Smarty\Ast\CallExpressionNode;
use Dev\Smarty\Ast\IdentifierExpressionNode;
use Dev\Smarty\Ast\LiteralExpressionNode;
use Dev\Smarty\Ast\UnaryExpressionNode;
use Dev\Smarty\Ast\VariableExpressionNode;
use Dev\Smarty\Parser\SmartyParser;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class OperatorAstShapeTest extends TestCase
{
    private SmartyParser $parser;

    protected function setUp(): void
    {
        $this->parser = new SmartyParser();
    }

    #[DataProvider('aliasOperatorCases')]
    public function testAliasOperatorsNormalizeToBinaryOperators(string $expr, string $expectedOperator): void
    {
        $condition = $this->parseIfCondition($expr);
        $this->assertInstanceOf(BinaryExpressionNode::class, $condition);
        $this->assertSame($expectedOperator, $condition->operator);
    }

    public function testIsInLowersToInArrayCall(): void
    {
        $condition = $this->parseIfCondition('$a is in $b');
        $this->assertInstanceOf(CallExpressionNode::class, $condition);
        $this->assertInstanceOf(IdentifierExpressionNode::class, $condition->callee);
        $this->assertSame('in_array', $condition->callee->name);
        $this->assertCount(2, $condition->arguments);
    }

    public function testIsNotInLowersToNotInArrayCall(): void
    {
        $condition = $this->parseIfCondition('$a is not in $b');
        $this->assertInstanceOf(UnaryExpressionNode::class, $condition);
        $this->assertSame('not', $condition->operator);
        $this->assertInstanceOf(CallExpressionNode::class, $condition->expression);
        $this->assertInstanceOf(IdentifierExpressionNode::class, $condition->expression->callee);
        $this->assertSame('in_array', $condition->expression->callee->name);
    }

    public function testIsDivByLowersToModuloEqualsZero(): void
    {
        $condition = $this->parseIfCondition('$a is div by 4');
        $this->assertModuloComparison($condition, '==');
    }

    public function testIsNotDivByLowersToModuloNotEqualsZero(): void
    {
        $condition = $this->parseIfCondition('$a is not div by 4');
        $this->assertModuloComparison($condition, '!=');
    }

    public function testIsEvenLowersToModuloEqualsZero(): void
    {
        $condition = $this->parseIfCondition('$a is even');
        $this->assertModuloComparison($condition, '==');
    }

    public function testIsNotEvenLowersToModuloNotEqualsZero(): void
    {
        $condition = $this->parseIfCondition('$a is not even');
        $this->assertModuloComparison($condition, '!=');
    }

    public function testIsOddLowersToModuloNotEqualsZero(): void
    {
        $condition = $this->parseIfCondition('$a is odd');
        $this->assertModuloComparison($condition, '!=');
    }

    public function testIsNotOddLowersToModuloEqualsZero(): void
    {
        $condition = $this->parseIfCondition('$a is not odd');
        $this->assertModuloComparison($condition, '==');
    }

    public function testIsEvenByLowersToDivisionThenModuloCheck(): void
    {
        $condition = $this->parseIfCondition('$a is even by $b');
        $this->assertModuloComparison($condition, '==', true);
    }

    public function testIsNotOddByLowersToDivisionThenModuloCheck(): void
    {
        $condition = $this->parseIfCondition('$a is not odd by $b');
        $this->assertModuloComparison($condition, '==', true);
    }

    public static function aliasOperatorCases(): array
    {
        return [
            ['$a eq $b', '=='],
            ['$a ne $b', '!='],
            ['$a neq $b', '!='],
            ['$a gt $b', '>'],
            ['$a lt $b', '<'],
            ['$a gte $b', '>='],
            ['$a ge $b', '>='],
            ['$a lte $b', '<='],
            ['$a le $b', '<='],
            ['$a mod $b', '%'],
        ];
    }

    private function parseIfCondition(string $expression)
    {
        $template = "{if $expression}ok{/if}";
        $result = $this->parser->parseString($template);
        $errors = array_filter($result->diagnostics, static fn ($d) => $d->severity->value === 'error');
        $this->assertCount(0, $errors, "Unexpected parser errors for expression: $expression");

        $block = $result->ast->children[0] ?? null;
        $this->assertNotNull($block);
        $this->assertNotEmpty($block->openTag->arguments);

        return $block->openTag->arguments[0]->value;
    }

    private function assertModuloComparison($condition, string $comparison, bool $expectsDivision = false): void
    {
        $this->assertInstanceOf(BinaryExpressionNode::class, $condition);
        $this->assertSame($comparison, $condition->operator);
        $this->assertInstanceOf(BinaryExpressionNode::class, $condition->left);
        $this->assertSame('%', $condition->left->operator);
        $this->assertInstanceOf(LiteralExpressionNode::class, $condition->right);
        $this->assertSame(0, $condition->right->value);

        if ($expectsDivision) {
            $this->assertInstanceOf(BinaryExpressionNode::class, $condition->left->left);
            $this->assertSame('/', $condition->left->left->operator);
        } else {
            $this->assertInstanceOf(VariableExpressionNode::class, $condition->left->left);
        }
    }
}
