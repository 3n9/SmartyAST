<?php

declare(strict_types=1);

namespace SmartyAst\Ast;

final class ErrorExpressionNode extends ExpressionNode
{
    public function __construct(
        SourceSpan $span,
        public string $message,
    ) {
        parent::__construct('ErrorExpression', $span);
    }

    public function toArray(): array
    {
        return [
            'kind' => $this->kind,
            'span' => SpanArray::from($this->span),
            'message' => $this->message,
        ];
    }
}
