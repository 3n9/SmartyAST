<?php

declare(strict_types=1);

namespace Dev\Smarty\Ast;

final class ArrayExpressionNode extends ExpressionNode
{
    /** @param list<ExpressionNode> $items */
    public function __construct(
        SourceSpan $span,
        public array $items,
    ) {
        parent::__construct('ArrayExpression', $span);
    }

    public function childExpressions(): array
    {
        return $this->items;
    }

    public function toArray(): array
    {
        return [
            'kind' => $this->kind,
            'span' => SpanArray::from($this->span),
            'items' => array_map(static fn (ExpressionNode $expression) => $expression->toArray(), $this->items),
        ];
    }
}
