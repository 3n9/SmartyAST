<?php

declare(strict_types=1);

namespace SmartyAst\Ast;

final class ModifierNode extends Node
{
    /** @param list<ExpressionNode> $arguments */
    public function __construct(
        SourceSpan $span,
        public string $name,
        public array $arguments,
    ) {
        parent::__construct('Modifier', $span);
    }

    public function toArray(): array
    {
        return [
            'kind' => $this->kind,
            'span' => SpanArray::from($this->span),
            'name' => $this->name,
            'arguments' => array_map(static fn (ExpressionNode $expression) => $expression->toArray(), $this->arguments),
        ];
    }
}
