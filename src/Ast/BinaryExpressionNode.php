<?php

declare(strict_types=1);

namespace Dev\Smarty\Ast;

final class BinaryExpressionNode extends ExpressionNode
{
    public function __construct(
        SourceSpan $span,
        public string $operator,
        public ExpressionNode $left,
        public ExpressionNode $right,
    ) {
        parent::__construct('BinaryExpression', $span);
    }

    public function childExpressions(): array
    {
        return [$this->left, $this->right];
    }

    public function toArray(): array
    {
        return [
            'kind' => $this->kind,
            'span' => SpanArray::from($this->span),
            'operator' => $this->operator,
            'left' => $this->left->toArray(),
            'right' => $this->right->toArray(),
        ];
    }
}
