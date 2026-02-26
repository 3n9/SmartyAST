<?php

declare(strict_types=1);

namespace SmartyAst\Ast;

final class PropertyFetchExpressionNode extends ExpressionNode
{
    public function __construct(
        SourceSpan $span,
        public ExpressionNode $target,
        public string $property,
        public string $operator,
    ) {
        parent::__construct('PropertyFetchExpression', $span);
    }

    public function childExpressions(): array
    {
        return [$this->target];
    }

    public function toArray(): array
    {
        return [
            'kind' => $this->kind,
            'span' => SpanArray::from($this->span),
            'target' => $this->target->toArray(),
            'property' => $this->property,
            'operator' => $this->operator,
        ];
    }
}
