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

    public function asString(): ?string
    {
        return $this->literalType === 'string' && is_string($this->value) ? $this->value : null;
    }

    public function asInt(): ?int
    {
        return is_int($this->value) ? $this->value : null;
    }

    public function asFloat(): ?float
    {
        return is_float($this->value) ? $this->value : null;
    }

    public function asBool(): ?bool
    {
        return is_bool($this->value) ? $this->value : null;
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
