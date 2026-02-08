<?php

declare(strict_types=1);

namespace Dev\Smarty\Ast;

final class BlockTagNode extends Node
{
    /** @param list<Node> $children
     *  @param list<ElseBranchNode> $elseBranches
     */
    public function __construct(
        SourceSpan $span,
        public TagNode $openTag,
        public array $children,
        public array $elseBranches,
        public ?SourceSpan $closeSpan,
    ) {
        parent::__construct('BlockTag', $span);
    }

    public function toArray(): array
    {
        return [
            'kind' => $this->kind,
            'span' => SpanArray::from($this->span),
            'openTag' => $this->openTag->toArray(),
            'children' => array_map(static fn (Node $node) => $node->toArray(), $this->children),
            'elseBranches' => array_map(static fn (ElseBranchNode $branch) => $branch->toArray(), $this->elseBranches),
            'closeSpan' => $this->closeSpan ? SpanArray::from($this->closeSpan) : null,
        ];
    }
}
