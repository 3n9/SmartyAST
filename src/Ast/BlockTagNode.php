<?php

declare(strict_types=1);

namespace SmartyAst\Ast;

final class BlockTagNode extends Node implements TagLike
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
        public bool $closeTrimLeft = false,
        public bool $closeTrimRight = false,
    ) {
        parent::__construct('BlockTag', $span);
    }

    public function resolveTag(): TagNode
    {
        return $this->openTag;
    }

    public function children(): array
    {
        return [$this->openTag, ...$this->children, ...$this->elseBranches];
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
            'closeTrimLeft' => $this->closeTrimLeft,
            'closeTrimRight' => $this->closeTrimRight,
        ];
    }
}
