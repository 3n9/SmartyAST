<?php

declare(strict_types=1);

namespace SmartyAst\Tests\Ast;

use PHPUnit\Framework\TestCase;
use SmartyAst\Parser\SmartyParser;
use SmartyAst\Ast\BlockTagNode;
use SmartyAst\Ast\BinaryExpressionNode;
use SmartyAst\Ast\CommentNode;
use SmartyAst\Ast\ElseBranchNode;
use SmartyAst\Ast\ExpressionNode;
use SmartyAst\Ast\LiteralExpressionNode;
use SmartyAst\Ast\ModifierChainExpressionNode;
use SmartyAst\Ast\Node;
use SmartyAst\Ast\PrintNode;
use SmartyAst\Ast\TagArgumentNode;
use SmartyAst\Ast\TagNode;
use SmartyAst\Ast\VariableExpressionNode;

/**
 * Verifies that Node::children() exposes complete subtrees so a single
 * recursive walk covers every node in the AST.
 */
final class NodeChildrenTest extends TestCase
{
    private SmartyParser $parser;

    protected function setUp(): void
    {
        $this->parser = new SmartyParser();
    }

    public function testPrintNodeChildrenIncludesExpression(): void
    {
        $result = $this->parser->parseString('{$foo}');
        $print  = $result->ast->children()[0];

        $this->assertInstanceOf(PrintNode::class, $print);
        $this->assertCount(1, $print->children());
        $this->assertInstanceOf(VariableExpressionNode::class, $print->children()[0]);
    }

    public function testTagNodeChildrenIncludesArguments(): void
    {
        $result = $this->parser->parseString('{include file="a.tpl"}');
        $tag    = $result->ast->children()[0];

        $this->assertInstanceOf(TagNode::class, $tag);
        $this->assertCount(1, $tag->children());
        $this->assertInstanceOf(TagArgumentNode::class, $tag->children()[0]);
    }

    public function testTagArgumentNodeChildrenIncludesValue(): void
    {
        $result = $this->parser->parseString('{include file="a.tpl"}');
        $tag    = $result->ast->children()[0];

        $this->assertInstanceOf(TagNode::class, $tag);
        $arg = $tag->children()[0];
        $this->assertInstanceOf(TagArgumentNode::class, $arg);
        $this->assertCount(1, $arg->children());
        $this->assertInstanceOf(LiteralExpressionNode::class, $arg->children()[0]);
    }

    public function testElseBranchNodeChildrenIncludesCondition(): void
    {
        $result = $this->parser->parseString('{if $a}{elseif $b}text{/if}');
        $block  = $result->ast->children()[0];

        $this->assertInstanceOf(BlockTagNode::class, $block);
        $elseBranch = $block->elseBranches[0];
        $this->assertInstanceOf(ElseBranchNode::class, $elseBranch);
        $this->assertNotNull($elseBranch->condition);

        $children = $elseBranch->children();
        $this->assertSame($elseBranch->condition, $children[0]);
    }

    public function testElseBranchNodeChildrenIncludesBodyAfterCondition(): void
    {
        $result = $this->parser->parseString('{if $a}{elseif $b}text{/if}');
        $block  = $result->ast->children()[0];
        $elseBranch = $block->elseBranches[0];

        $children = $elseBranch->children();
        // First child is condition, remaining are body nodes.
        $this->assertGreaterThan(1, count($children));
        $this->assertSame($elseBranch->condition, $children[0]);
    }

    public function testElseBranchWithoutConditionChildrenIsJustBody(): void
    {
        $result = $this->parser->parseString('{if $a}{else}text{/if}');
        $block  = $result->ast->children()[0];
        $elseBranch = $block->elseBranches[0];

        $this->assertNull($elseBranch->condition);
        // Children should be the body nodes only (no null prepended).
        foreach ($elseBranch->children() as $child) {
            $this->assertInstanceOf(Node::class, $child);
        }
    }

    public function testCommentNodeChildrenIncludesAnnotations(): void
    {
        $result  = $this->parser->parseString('{* @param string $name *}');
        $comment = $result->ast->children()[0];

        $this->assertInstanceOf(CommentNode::class, $comment);
        // Annotations are populated by PhpDocTemplateAnnotationParser.
        $this->assertNotEmpty($comment->annotations);
        $children = $comment->children();
        $this->assertSame($comment->annotations, $children);
    }

    public function testExpressionNodeChildrenDelegatesToChildExpressions(): void
    {
        $result = $this->parser->parseString('{$a && $b}');
        $print  = $result->ast->children()[0];

        $this->assertInstanceOf(PrintNode::class, $print);
        $expr = $print->children()[0];
        // The print expression is a BinaryExpressionNode.
        $this->assertInstanceOf(BinaryExpressionNode::class, $expr);
        // children() must return left + right.
        $exprChildren = $expr->children();
        $this->assertCount(2, $exprChildren);
        $this->assertInstanceOf(ExpressionNode::class, $exprChildren[0]);
        $this->assertInstanceOf(ExpressionNode::class, $exprChildren[1]);
    }

    public function testModifierChainExpressionChildrenDelegatesToChildExpressions(): void
    {
        $result = $this->parser->parseString('{$var|upper}');
        $print  = $result->ast->children()[0];

        $this->assertInstanceOf(PrintNode::class, $print);
        $chain = $print->children()[0];
        $this->assertInstanceOf(ModifierChainExpressionNode::class, $chain);
        // children() should return at least the base expression.
        $this->assertNotEmpty($chain->children());
        $this->assertSame($chain->base, $chain->children()[0]);
    }

    public function testFullTreeWalkReachesAllVariables(): void
    {
        $result = $this->parser->parseString('{include file=$path}{$var|upper}');

        $varNames = [];
        $this->collectVarNamesRecursive($result->ast, $varNames);

        $this->assertContains('path', $varNames);
        $this->assertContains('var', $varNames);
    }

    /** @param list<string> &$names */
    private function collectVarNamesRecursive(Node $node, array &$names): void
    {
        if ($node instanceof VariableExpressionNode) {
            $names[] = $node->name;
        }
        foreach ($node->children() as $child) {
            $this->collectVarNamesRecursive($child, $names);
        }
    }
}
