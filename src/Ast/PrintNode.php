<?php

declare(strict_types=1);

namespace SmartyAst\Ast;

final class PrintNode extends Node
{
    public function __construct(
        SourceSpan $span,
        public ExpressionNode $expression,
        public bool $trimLeft = false,
        public bool $trimRight = false,
    ) {
        parent::__construct('Print', $span);
    }

    public function toArray(): array
    {
        return [
            'kind' => $this->kind,
            'span' => SpanArray::from($this->span),
            'trimLeft' => $this->trimLeft,
            'trimRight' => $this->trimRight,
            'expression' => $this->expression->toArray(),
        ];
    }
}
