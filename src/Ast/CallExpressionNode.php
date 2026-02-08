<?php

declare(strict_types=1);

namespace Dev\Smarty\Ast;

final class CallExpressionNode extends ExpressionNode
{
    /** @param list<ExpressionNode> $arguments */
    public function __construct(
        SourceSpan $span,
        public ExpressionNode $callee,
        public array $arguments,
    ) {
        parent::__construct('CallExpression', $span);
    }

    public function toArray(): array
    {
        return [
            'kind' => $this->kind,
            'span' => SpanArray::from($this->span),
            'callee' => $this->callee->toArray(),
            'arguments' => array_map(static fn (ExpressionNode $expression) => $expression->toArray(), $this->arguments),
        ];
    }
}
