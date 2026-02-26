<?php

declare(strict_types=1);

namespace SmartyAst\Ast;

final class NamedArgumentExpressionNode extends ExpressionNode
{
    public function __construct(
        SourceSpan $span,
        public string $name,
        public ExpressionNode $value,
    ) {
        parent::__construct('NamedArgument', $span);
    }

    public function childExpressions(): array
    {
        return [$this->value];
    }

    public function toArray(): array
    {
        return [
            'kind' => $this->kind,
            'span' => SpanArray::from($this->span),
            'name' => $this->name,
            'value' => $this->value->toArray(),
        ];
    }
}
