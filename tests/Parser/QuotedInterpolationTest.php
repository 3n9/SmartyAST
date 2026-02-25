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
use PHPUnit\Framework\TestCase;

final class QuotedInterpolationTest extends TestCase
{
    private SmartyParser $parser;

    protected function setUp(): void
    {
        $this->parser = new SmartyParser();
    }

    public function testDoubleQuotedVariableInterpolation(): void
    {
        $arg = $this->parseFuncVarArgument('{func var="test $foo test"}');
        $this->assertContains('foo', $this->collectVariableNames($arg->value));
    }

    public function testDoubleQuotedUnderscoreVariableInterpolation(): void
    {
        $arg = $this->parseFuncVarArgument('{func var="test $foo_bar test"}');
        $this->assertContains('foo_bar', $this->collectVariableNames($arg->value));
    }

    public function testBacktickArrayOffsetInterpolation(): void
    {
        $arg = $this->parseFuncVarArgument('{func var="test `$foo[0]` test"}');
        $this->assertTrue($this->hasNodeOfType($arg->value, ArrayAccessExpressionNode::class));
    }

    public function testBacktickArrayKeyInterpolation(): void
    {
        $arg = $this->parseFuncVarArgument('{func var="test `$foo[bar]` test"}');
        $this->assertTrue($this->hasNodeOfType($arg->value, ArrayAccessExpressionNode::class));
    }

    public function testDotWithoutBackticksOnlySeesVariable(): void
    {
        $arg = $this->parseFuncVarArgument('{func var="test $foo.bar test"}');
        $this->assertContains('foo', $this->collectVariableNames($arg->value));
        $this->assertFalse($this->hasNodeOfType($arg->value, PropertyFetchExpressionNode::class));
    }

    public function testDotInsideBackticksSeesPropertyFetch(): void
    {
        $arg = $this->parseFuncVarArgument('{func var="test `$foo.bar` test"}');
        $this->assertTrue($this->hasNodeOfType($arg->value, PropertyFetchExpressionNode::class));
    }

    public function testModifierOutsideQuotesStillApplies(): void
    {
        $arg = $this->parseFuncVarArgument('{func var="test `$foo.bar` test"|escape}');
        $this->assertInstanceOf(ModifierChainExpressionNode::class, $arg->value);
    }

    public function testModifierInsideEmbeddedExpressionWorks(): void
    {
        $arg = $this->parseFuncVarArgument('{func var="test {$foo|escape} test"}');
        $this->assertTrue($this->hasNodeOfType($arg->value, ModifierChainExpressionNode::class));
    }

    public function testEmbeddedFunctionCallInQuotesWorks(): void
    {
        $arg = $this->parseFuncVarArgument('{func var="test {time()} test"}');
        $this->assertTrue($this->hasNodeOfType($arg->value, CallExpressionNode::class));
    }

    public function testEmbeddedPluginNameInQuotesWorks(): void
    {
        $arg = $this->parseFuncVarArgument('{func var="test {counter} test"}');
        $this->assertTrue($this->hasNodeOfType($arg->value, IdentifierExpressionNode::class));
    }

    public function testSingleQuotedStringDoesNotInterpolateVariables(): void
    {
        $arg = $this->parseFuncVarArgument("{func var='subdir/\$tpl_name.tpl'}");
        $this->assertFalse($this->hasNodeOfType($arg->value, VariableExpressionNode::class));
    }

    public function testEmbeddedIfBlockInQuotesSeesConditionVariable(): void
    {
        $arg = $this->parseFuncVarArgument('{func var="variable foo is {if !$foo}not {/if} defined"}');
        $this->assertContains('foo', $this->collectVariableNames($arg->value));
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

    /** @return list<string> */
    private function collectVariableNames(ExpressionNode $expression): array
    {
        $names = [];
        $this->walkForVariableNames($expression, $names);
        return array_values(array_unique($names));
    }

    /**
     * @param list<string> $names
     */
    private function walkForVariableNames(mixed $value, array &$names): void
    {
        if ($value instanceof VariableExpressionNode) {
            $names[] = $value->name;
        }

        if ($value instanceof ExpressionNode || $value instanceof Node) {
            foreach (get_object_vars($value) as $propertyValue) {
                $this->walkForVariableNames($propertyValue, $names);
            }
            return;
        }

        if (is_array($value)) {
            foreach ($value as $item) {
                $this->walkForVariableNames($item, $names);
            }
        }
    }

    private function hasNodeOfType(ExpressionNode $expression, string $className): bool
    {
        if ($expression instanceof $className) {
            return true;
        }

        foreach (get_object_vars($expression) as $value) {
            if ($value instanceof ExpressionNode && $this->hasNodeOfType($value, $className)) {
                return true;
            }

            if (is_array($value)) {
                foreach ($value as $item) {
                    if ($item instanceof ExpressionNode && $this->hasNodeOfType($item, $className)) {
                        return true;
                    }
                    if ($item instanceof Node) {
                        foreach (get_object_vars($item) as $nested) {
                            if ($nested instanceof ExpressionNode && $this->hasNodeOfType($nested, $className)) {
                                return true;
                            }
                        }
                    }
                }
            }
        }

        return false;
    }
}
