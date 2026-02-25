<?php

declare(strict_types=1);

namespace SmartyAst\Ast;

final class UnaryExpressionNode extends ExpressionNode
{
    public function __construct(
        SourceSpan $span,
        public string $operator,
        public ExpressionNode $expression,
    ) {
        parent::__construct('UnaryExpression', $span);
    }

    public function childExpressions(): array
    {
        return [$this->expression];
    }

    public function toArray(): array
    {
        return [
            'kind' => $this->kind,
            'span' => SpanArray::from($this->span),
            'operator' => $this->operator,
            'expression' => $this->expression->toArray(),
        ];
    }
}
