<?php

declare(strict_types=1);

namespace Dev\Smarty\Ast;

final class TernaryExpressionNode extends ExpressionNode
{
    public function __construct(
        SourceSpan $span,
        public ExpressionNode $condition,
        public ExpressionNode $ifTrue,
        public ExpressionNode $ifFalse,
    ) {
        parent::__construct('TernaryExpression', $span);
    }

    public function toArray(): array
    {
        return [
            'kind' => $this->kind,
            'span' => SpanArray::from($this->span),
            'condition' => $this->condition->toArray(),
            'ifTrue' => $this->ifTrue->toArray(),
            'ifFalse' => $this->ifFalse->toArray(),
        ];
    }
}
