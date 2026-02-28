<?php

declare(strict_types=1);

namespace SmartyAst\Visitor;

use SmartyAst\Ast\Node;

/**
 * Visitor interface for walking an AST.
 *
 * Implement this interface and pass an instance to {@see Node::walk()} to
 * receive callbacks as each node is entered and exited during traversal.
 */
interface NodeVisitorInterface
{
    /**
     * Called when traversal enters a node (before its children are visited).
     */
    public function enterNode(Node $node): void;

    /**
     * Called when traversal exits a node (after all children have been visited).
     */
    public function leaveNode(Node $node): void;
}
