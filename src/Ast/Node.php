<?php

declare(strict_types=1);

namespace SmartyAst\Ast;

use SmartyAst\Visitor\NodeVisitorInterface;

abstract class Node
{
    public function __construct(
        public readonly string $kind,
        public readonly SourceSpan $span,
    ) {
    }

    /**
     * @return list<Node>
     */
    public function children(): array
    {
        return [];
    }

    /**
     * Walks this node and all its descendants depth-first, calling
     * {@see NodeVisitorInterface::enterNode()} before children and
     * {@see NodeVisitorInterface::leaveNode()} after.
     */
    public function walk(NodeVisitorInterface $visitor): void
    {
        $visitor->enterNode($this);
        foreach ($this->children() as $child) {
            $child->walk($visitor);
        }
        $visitor->leaveNode($this);
    }

    /**
     * @return array<string,mixed>
     */
    abstract public function toArray(): array;

    public function toJson(int $flags = 0): string
    {
        return (string) json_encode($this->toArray(), $flags);
    }
}
