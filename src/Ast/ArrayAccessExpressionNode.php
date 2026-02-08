<?php

declare(strict_types=1);

namespace Dev\Smarty\Ast;

final class ArrayAccessExpressionNode extends ExpressionNode
{
    public function __construct(
        SourceSpan $span,
        public ExpressionNode $target,
        public ExpressionNode $index,
    ) {
        parent::__construct('ArrayAccessExpression', $span);
    }

    public function toArray(): array
    {
        return [
            'kind' => $this->kind,
            'span' => SpanArray::from($this->span),
            'target' => $this->target->toArray(),
            'index' => $this->index->toArray(),
        ];
    }
}
