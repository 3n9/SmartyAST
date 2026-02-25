<?php

declare(strict_types=1);

namespace SmartyAst\Ast;

final class IdentifierExpressionNode extends ExpressionNode
{
    public function __construct(
        SourceSpan $span,
        public string $name,
    ) {
        parent::__construct('IdentifierExpression', $span);
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
