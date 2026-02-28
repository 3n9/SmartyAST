<?php

declare(strict_types=1);

namespace SmartyAst\Ast;

final class ElseBranchNode extends Node
{
    /** @param list<Node> $children */
    public function __construct(
        SourceSpan $span,
        public string $name,
        public ?ExpressionNode $condition,
        public array $children,
    ) {
        parent::__construct('ElseBranch', $span);
    }

    public function children(): array
    {
        if ($this->condition !== null) {
            return [$this->condition, ...$this->children];
        }
        return $this->children;
    }

    public function toArray(): array
    {
        return [
            'kind' => $this->kind,
            'span' => SpanArray::from($this->span),
            'name' => $this->name,
            'condition' => $this->condition?->toArray(),
            'children' => array_map(static fn (Node $node) => $node->toArray(), $this->children),
        ];
    }
}
