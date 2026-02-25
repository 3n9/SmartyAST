<?php

declare(strict_types=1);

namespace SmartyAst\Tests\Parser;

use SmartyAst\Ast\ArrayAccessExpressionNode;
use SmartyAst\Ast\BinaryExpressionNode;
use SmartyAst\Ast\CallExpressionNode;
use SmartyAst\Ast\ExpressionNode;
use SmartyAst\Ast\IdentifierExpressionNode;
use SmartyAst\Ast\ModifierChainExpressionNode;
use SmartyAst\Ast\Node;
use SmartyAst\Ast\PropertyFetchExpressionNode;
use SmartyAst\Ast\TagArgumentNode;
use SmartyAst\Ast\TagNode;
use SmartyAst\Ast\VariableExpressionNode;
use SmartyAst\Parser\SmartyParser;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class QuotedInterpolationAstSnapshotTest extends TestCase
{
    private SmartyParser $parser;

    protected function setUp(): void
    {
        $this->parser = new SmartyParser();
    }

    #[DataProvider('cases')]
    public function testExpressionShapeSnapshots(
        string $template,
        array $expectedVariables,
        array $requiredNodeKinds,
        bool $expectTopLevelModifierChain
    ): void {
        $arg = $this->parseFuncVarArgument($template);
        $shape = $this->expressionShape($arg->value);

        foreach ($expectedVariables as $variable) {
            $this->assertContains($variable, $shape['variables'], "Expected variable '$variable' in: $template");
        }

        foreach ($requiredNodeKinds as $kind) {
            $this->assertContains($kind, $shape['nodeKinds'], "Expected node kind '$kind' in: $template");
        }

        if ($expectTopLevelModifierChain) {
            $this->assertInstanceOf(ModifierChainExpressionNode::class, $arg->value);
        }
    }

    public static function cases(): array
    {
        return [
            'double_quoted_var' => [
                '{func var="test $foo test"}',
                ['foo'],
                ['BinaryExpression', 'VariableExpression'],
                false,
            ],
            'double_quoted_var_underscore' => [
                '{func var="test $foo_bar test"}',
                ['foo_bar'],
                ['BinaryExpression', 'VariableExpression'],
                false,
            ],
            'backtick_array_index' => [
                '{func var="test `$foo[0]` test"}',
                ['foo'],
                ['BinaryExpression', 'ArrayAccessExpression'],
                false,
            ],
            'backtick_array_key' => [
                '{func var="test `$foo[bar]` test"}',
                ['foo'],
                ['BinaryExpression', 'ArrayAccessExpression'],
                false,
            ],
            'dot_without_backticks' => [
                '{func var="test $foo.bar test"}',
                ['foo'],
                ['BinaryExpression', 'VariableExpression'],
                false,
            ],
            'dot_inside_backticks' => [
                '{func var="test `$foo.bar` test"}',
                ['foo'],
                ['BinaryExpression', 'PropertyFetchExpression'],
                false,
            ],
            'modifier_outside_quotes' => [
                '{func var="test `$foo.bar` test"|escape}',
                ['foo'],
                ['ModifierChainExpression', 'PropertyFetchExpression'],
                true,
            ],
            'modifier_inside_embedded_expression' => [
                '{func var="test {$foo|escape} test"}',
                ['foo'],
                ['BinaryExpression', 'ModifierChainExpression'],
                false,
            ],
            'embedded_function_call' => [
                '{func var="test {time()} test"}',
                [],
                ['BinaryExpression', 'CallExpression'],
                false,
            ],
            'embedded_plugin_name' => [
                '{func var="test {counter} test"}',
                [],
                ['BinaryExpression', 'IdentifierExpression'],
                false,
            ],
            'embedded_if_block' => [
                '{func var="variable foo is {if !$foo}not {/if} defined"}',
                ['foo'],
                ['BinaryExpression', 'UnaryExpression', 'VariableExpression'],
                false,
            ],
        ];
    }

    private function parseFuncVarArgument(string $template): TagArgumentNode
    {
        $result = $this->parser->parseString($template);
        $this->assertCount(0, array_filter($result->diagnostics, static fn ($d) => $d->severity->value === 'error'));

        $tag = null;
        foreach ($result->ast->children as $child) {
            if ($child instanceof TagNode && $child->name === 'func') {
                $tag = $child;
                break;
            }
        }

        $this->assertNotNull($tag);
        foreach ($tag->arguments as $argument) {
            if ($argument->name === 'var') {
                return $argument;
            }
        }

        $this->fail('Missing var argument on func tag.');
    }

    /**
     * @return array{variables:list<string>,nodeKinds:list<string>}
     */
    private function expressionShape(ExpressionNode $expression): array
    {
        $variables = [];
        $nodeKinds = [];
        $this->walkShape($expression, $variables, $nodeKinds);

        return [
            'variables' => array_values(array_unique($variables)),
            'nodeKinds' => array_values(array_unique($nodeKinds)),
        ];
    }

    /**
     * @param list<string> $variables
     * @param list<string> $nodeKinds
     */
    private function walkShape(mixed $value, array &$variables, array &$nodeKinds): void
    {
        if ($value instanceof ExpressionNode || $value instanceof Node) {
            $nodeKinds[] = $value->kind;
        }

        if ($value instanceof VariableExpressionNode) {
            $variables[] = $value->name;
        }

        if ($value instanceof ExpressionNode || $value instanceof Node) {
            foreach (get_object_vars($value) as $propertyValue) {
                $this->walkShape($propertyValue, $variables, $nodeKinds);
            }
            return;
        }

        if (is_array($value)) {
            foreach ($value as $item) {
                $this->walkShape($item, $variables, $nodeKinds);
            }
        }
    }
}
