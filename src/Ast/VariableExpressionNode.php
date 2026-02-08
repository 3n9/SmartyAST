<?php

declare(strict_types=1);

namespace Dev\Smarty\Ast;

final class VariableExpressionNode extends ExpressionNode
{
    public function __construct(
        SourceSpan $span,
        public string $name,
    ) {
        parent::__construct('VariableExpression', $span);
    }

    public function toArray(): array
    {
        return [
            'kind' => $this->kind,
            'span' => SpanArray::from($this->span),
            'name' => $this->name,
        ];
    }
}
