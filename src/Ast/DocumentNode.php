<?php

declare(strict_types=1);

namespace SmartyAst\Ast;

final class DocumentNode extends Node
{
    /** @param list<Node> $children */
    public function __construct(
        SourceSpan $span,
        public array $children,
    ) {
        parent::__construct('Document', $span);
    }

    public function children(): array
    {
        return $this->children;
    }

    public function toArray(): array
    {
        return [
            'kind' => $this->kind,
            'span' => SpanArray::from($this->span),
            'children' => array_map(static fn (Node $node) => $node->toArray(), $this->children),
        ];
    }
}
