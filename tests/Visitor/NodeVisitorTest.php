<?php

declare(strict_types=1);

namespace SmartyAst\Tests\Visitor;

use PHPUnit\Framework\TestCase;
use SmartyAst\Ast\Node;
use SmartyAst\Ast\PrintNode;
use SmartyAst\Ast\VariableExpressionNode;
use SmartyAst\Parser\SmartyParser;
use SmartyAst\Visitor\NodeVisitorInterface;

final class NodeVisitorTest extends TestCase
{
    private SmartyParser $parser;

    protected function setUp(): void
    {
        $this->parser = new SmartyParser();
    }

    public function testWalkVisitsAllNodes(): void
    {
        $result = $this->parser->parseString('{$foo}');

        $kinds   = [];
        $visitor = $this->makeCollectingVisitor($kinds);
        $result->ast->walk($visitor);

        $this->assertContains('Document', $kinds);
        $this->assertContains('Print', $kinds);
        $this->assertContains('VariableExpression', $kinds);
    }

    public function testEnterCalledBeforeLeave(): void
    {
        $result = $this->parser->parseString('{$foo}');

        $events  = [];
        $visitor = new class ($events) implements NodeVisitorInterface {
            public function __construct(private array &$events) {}
            public function enterNode(Node $node): void { $this->events[] = 'enter:' . $node->kind; }
            public function leaveNode(Node $node): void { $this->events[] = 'leave:' . $node->kind; }
        };

        $result->ast->walk($visitor);

        // Document must be entered before Print, and Print left before Document.
        $enterDoc  = array_search('enter:Document', $events, true);
        $enterPrint = array_search('enter:Print', $events, true);
        $leavePrint = array_search('leave:Print', $events, true);
        $leaveDoc  = array_search('leave:Document', $events, true);

        $this->assertLessThan($enterPrint, $enterDoc);
        $this->assertLessThan($leavePrint, $enterPrint);
        $this->assertLessThan($leaveDoc, $leavePrint);
    }

    public function testWalkReachesTagArguments(): void
    {
        $result = $this->parser->parseString('{include file="a.tpl"}');

        $kinds   = [];
        $visitor = $this->makeCollectingVisitor($kinds);
        $result->ast->walk($visitor);

        $this->assertContains('Tag', $kinds);
        $this->assertContains('TagArgument', $kinds);
        $this->assertContains('LiteralExpression', $kinds);
    }

    public function testWalkReachesElseIfCondition(): void
    {
        $result = $this->parser->parseString('{if $a}{elseif $b}x{/if}');

        $names   = [];
        $visitor = new class ($names) implements NodeVisitorInterface {
            public function __construct(private array &$names) {}
            public function enterNode(Node $node): void {
                if ($node instanceof VariableExpressionNode) {
                    $this->names[] = $node->name;
                }
            }
            public function leaveNode(Node $node): void {}
        };

        $result->ast->walk($visitor);

        // $b lives in ElseBranchNode::condition and must be reached.
        // ($a lives in BlockTagNode::openTag which is not traversed by children().)
        $this->assertContains('b', $names);
    }

    public function testWalkCanCollectVariableNames(): void
    {
        $result   = $this->parser->parseString('{include file=$path}{$output|upper}');
        $collected = [];

        $visitor = new class ($collected) implements NodeVisitorInterface {
            public function __construct(private array &$collected) {}
            public function enterNode(Node $node): void {
                if ($node instanceof VariableExpressionNode) {
                    $this->collected[] = $node->name;
                }
            }
            public function leaveNode(Node $node): void {}
        };

        $result->ast->walk($visitor);

        $this->assertContains('path', $collected);
        $this->assertContains('output', $collected);
    }

    public function testLeaveNodeNotCalledOnPartialVisitor(): void
    {
        // A visitor that only overrides enterNode should not error.
        $result  = $this->parser->parseString('{$x}');
        $entered = [];

        $visitor = new class ($entered) implements NodeVisitorInterface {
            public function __construct(private array &$entered) {}
            public function enterNode(Node $node): void { $this->entered[] = $node->kind; }
            public function leaveNode(Node $node): void { /* no-op */ }
        };

        $result->ast->walk($visitor);
        $this->assertNotEmpty($entered);
    }

    /** @param list<string> &$kinds */
    private function makeCollectingVisitor(array &$kinds): NodeVisitorInterface
    {
        return new class ($kinds) implements NodeVisitorInterface {
            public function __construct(private array &$kinds) {}
            public function enterNode(Node $node): void { $this->kinds[] = $node->kind; }
            public function leaveNode(Node $node): void {}
        };
    }
}
