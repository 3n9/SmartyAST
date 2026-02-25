<?php

declare(strict_types=1);

namespace SmartyAst\Ast;

final class LiteralExpressionNode extends ExpressionNode
{
    public function __construct(
        SourceSpan $span,
        public string $literalType,
        public string|int|float|bool|null $value,
    ) {
        parent::__construct('LiteralExpression', $span);
    }

    public function toArray(): array
    {
        return [
            'kind' => $this->kind,
            'span' => SpanArray::from($this->span),
            'literalType' => $this->literalType,
            'value' => $this->value,
        ];
    }
}
